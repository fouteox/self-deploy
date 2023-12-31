<?php

namespace App\Jobs;

use App\Models\CouldNotConnectToServerException;
use App\Models\Daemon;
use App\Models\NoConnectionSelectedException;
use App\Models\Server;
use App\Models\TaskFailedException;
use App\Models\User;
use App\Tasks\DeleteFile;
use App\Tasks\ReloadSupervisor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class UninstallDaemon implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public Daemon $daemon, public ?User $user = null)
    {
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
        tap($this->daemon->server, function (Server $server) {
            $server->runTask(new DeleteFile($this->daemon->path()))->asRoot()->dispatch();
            $server->runTask(new ReloadSupervisor)->asRoot()->dispatch();
        });

        $this->daemon->delete();
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        $this->daemon->forceFill(['uninstallation_failed_at' => now()])->save();

        $this->daemon->server
            ->exceptionHandler()
            ->notify($this->user)
            ->about($exception)
            ->withReference(__("Uninstallation of daemon ':daemon'", ['daemon' => "`{$this->daemon->command}`"]))
            ->send();
    }
}
