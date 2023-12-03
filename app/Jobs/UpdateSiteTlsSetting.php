<?php

namespace App\Jobs;

use App\CaddyfilePatcher;
use App\Models\Certificate;
use App\Models\CouldNotConnectToServerException;
use App\Models\NoConnectionSelectedException;
use App\Models\Site;
use App\Models\TaskFailedException;
use App\Models\TlsSetting;
use App\Models\User;
use App\Tasks\GetFile;
use App\Tasks\PrettifyCaddyfile;
use App\Tasks\UpdateCaddyfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class UpdateSiteTlsSetting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public Site $site,
        public TlsSetting $tlsSetting,
        public ?Certificate $certificate = null,
        public ?User $user = null,
    ) {
    }

    /**
     * Execute the job.
     *
     * @throws CouldNotConnectToServerException
     * @throws NoConnectionSelectedException
     * @throws TaskFailedException
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        $path = $this->site->files()->caddyfile()->path;

        // Prettify the Caddyfile before we start.
        $this->site->runTaskAsUser(new PrettifyCaddyfile($path))->throw()->dispatch();

        // Get the current Caddyfile.
        $currentCaddyfile = $this->site->runTaskAsUser(new GetFile($path))->throw()->dispatch()->getBuffer();

        // Patch the Caddyfile.

        $patcher = app()->makeWith(CaddyfilePatcher::class, ['site' => $this->site, 'caddyfile' => $currentCaddyfile]);
        $patcher->replaceTlsSnippet($this->tlsSetting, $this->certificate);

        if ($this->site->tls_setting === TlsSetting::Off) {
            $patcher->replacePort(443);
        } elseif ($this->tlsSetting === TlsSetting::Off) {
            $patcher->replacePort(80);
        }

        $newCaddyfile = $patcher->get();

        // Update the Caddyfile on the server.
        $this->site->runTaskAsUser(new UpdateCaddyfile($this->site, $newCaddyfile))->throw()->dispatch();

        $this->site->forceFill([
            'pending_tls_update_since' => null,
            'tls_setting' => $this->tlsSetting,
        ])->save();

        $this->site->certificates()->update(['is_active' => false]);

        $this->certificate?->forceFill([
            'is_active' => true,
            'uploaded_at' => now(),
        ])->save();
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        $this->site->forceFill([
            'pending_tls_update_since' => null,
        ])->save();

        $this->site->server
            ->exceptionHandler()
            ->notify($this->user)
            ->about($exception)
            ->withReference(__('Update the TLS setting for :site', ['site' => $this->site->address]))
            ->send();
    }
}
