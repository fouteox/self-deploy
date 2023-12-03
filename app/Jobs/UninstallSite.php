<?php

namespace App\Jobs;

use App\Models\CouldNotConnectToServerException;
use App\Models\NoConnectionSelectedException;
use App\Models\Server;
use App\Models\TaskFailedException;
use App\Tasks\DeleteFile;
use App\Tasks\UpdateCaddySiteImports;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UninstallSite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public Server $server, public $sitePath)
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
        $this->server->runTask(new UpdateCaddySiteImports($this->server))->throw()->asRoot()->dispatch();
        $this->server->runTask(new DeleteFile($this->sitePath))->throw()->asRoot()->dispatch();
    }
}
