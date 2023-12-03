<?php

namespace App\Jobs;

use App\Models\CouldNotConnectToServerException;
use App\Models\Daemon;
use App\Models\NoConnectionSelectedException;
use App\Models\TaskFailedException;
use App\Models\User;
use App\Tasks\ReloadSupervisor;
use App\View\Components\SupervisorProgram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class InstallDaemon implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public Daemon $daemon,
        public ?User $user = null,
    ) {
        $this->onQueue('commands');
    }

    /**
     * Execute the job.
     *
     * @throws CouldNotConnectToServerException
     * @throws NoConnectionSelectedException
     * @throws TaskFailedException
     */
    public function handle(): void
    {
        $contents = SupervisorProgram::build($this->daemon);

        $this->daemon->server->uploadAsRoot($this->daemon->path(), $contents);

        $this->daemon->server->runTask(ReloadSupervisor::class)->asRoot()->dispatch();

        $this->daemon->forceFill([
            'installed_at' => now(),
            'installation_failed_at' => null,
        ])->save();
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        $this->daemon->forceFill(['installation_failed_at' => now()])->save();

        $this->daemon->server
            ->exceptionHandler()
            ->notify($this->user)
            ->about($exception)
            ->withReference(__("Installation of daemon ':daemon'", ['daemon' => "`{$this->daemon->command}`"]))
            ->send();
    }
}
