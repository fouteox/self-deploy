<?php

namespace App\Jobs;

use App\Models\Backup;
use App\Models\CouldNotConnectToServerException;
use App\Models\NoConnectionSelectedException;
use App\Models\TaskFailedException;
use App\Models\User;
use App\Tasks\InstallEddyBackupCli;
use App\View\Components\Backup as BackupView;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class InstallBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 150;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public Backup $backup, public ?User $user = null)
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
        $this->backup->server->runTask(new InstallEddyBackupCli($this->backup->server))
            ->asRoot()
            ->dispatch();

        $contents = BackupView::build($this->backup);

        $this->backup->server->uploadAsRoot($this->backup->cronPath(), $contents);

        $this->backup->forceFill([
            'installed_at' => now(),
            'installation_failed_at' => null,
        ])->save();
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        $this->backup->forceFill(['installation_failed_at' => now()])->save();

        $this->backup->server
            ->exceptionHandler()
            ->notify($this->user)
            ->about($exception)
            ->withReference(__("Installation of backup ':backup'", ['backup' => "`{$this->backup->name}`"]))
            ->send();
    }
}
