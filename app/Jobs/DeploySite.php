<?php

namespace App\Jobs;

use App\Models\CouldNotConnectToServerException;
use App\Models\Deployment;
use App\Models\NoConnectionSelectedException;
use App\Models\TaskFailedException;
use App\Tasks;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeploySite implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public Deployment $deployment, public array $environmentVariables = [])
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
        $taskClass = $this->deployment->site->zero_downtime_deployment
            ? Tasks\DeploySiteWithoutDowntime::class
            : Tasks\DeploySite::class;

        $task = new $taskClass($this->deployment, $this->environmentVariables);

        $server = $this->deployment->site->server;

        $taskModel = $server->runTask($task)
            ->asUser()
            ->inBackground()
            ->keepTrackInBackground()
            ->updateLogIntervalInSeconds(10)
            ->dispatch();

        dispatch(new CleanupPendingSiteDeployment($this->deployment))->delay(10 * 60);

        $this->deployment->update(['task_id' => $taskModel->id]);
    }
}
