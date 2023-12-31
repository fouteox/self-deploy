<?php

namespace App\Jobs;

use App\Models\Database;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class UninstallDatabase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public Database $database, public ?User $user = null)
    {
    }

    /**
     * Execute the job.
     *
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        $this->database->server
            ->databaseManager()
            ->dropDatabase($this->database->name);

        $this->database->delete();
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        $this->database->forceFill(['uninstallation_failed_at' => now()])->save();

        $this->database->server
            ->exceptionHandler()
            ->notify($this->user)
            ->about($exception)
            ->withReference(__("Uninstallation of database ':database'", ['database' => "`{$this->database->name}`"]))
            ->send();
    }
}
