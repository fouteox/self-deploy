<?php

namespace App\Jobs;

use App\Models\CouldNotConnectToServerException;
use App\Models\NoConnectionSelectedException;
use App\Models\Server;
use App\Models\TaskFailedException;
use App\Server\Software;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MakeSoftwareDefaultOnServer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Server $server, public Software $software)
    {
        //
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
        $task = $this->software->updateAlternativesTask();

        if (! $task) {
            return;
        }

        $this->server->runTask($task)
            ->asRoot()
            ->inBackground()
            ->throw()
            ->dispatch();
    }
}
