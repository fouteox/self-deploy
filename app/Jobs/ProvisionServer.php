<?php

namespace App\Jobs;

use App\Infrastructure\Entities\ServerStatus;
use App\Models\CouldNotConnectToServerException;
use App\Models\NoConnectionSelectedException;
use App\Models\Server;
use App\Models\ServerTaskDispatcher;
use App\Models\Task;
use App\Models\TaskFailedException;
use App\Tasks\ProvisionFreshServer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProvisionServer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public Server $server,
        public ?Collection $sshKeys = null
    ) {
        $this->sshKeys ??= new Collection;
    }

    /**
     * Execute the job.
     *
     * @throws CouldNotConnectToServerException
     * @throws NoConnectionSelectedException
     * @throws TaskFailedException
     */
    public function handle(): Task
    {
        $this->server->forceFill(['status' => ServerStatus::Provisioning])->save();

        /** @var Task */
        return $this->server
            ->runTask(new ProvisionFreshServer($this->server, $this->sshKeys))
            ->asRoot()
            ->keepTrackInBackground()
            ->when(app()->environment('local'), fn (ServerTaskDispatcher $dispatcher) => $dispatcher->updateLogIntervalInSeconds(10))
            ->dispatch();
    }

    /**
     * Handle a job failure.
     */
    public function failed(): void
    {
        dispatch(new CleanupFailedServerProvisioning($this->server));
    }
}
