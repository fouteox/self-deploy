<?php

namespace App\Jobs;

use App\Models\PendingDeploymentException;
use App\Models\Site;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateDeployment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public Site $site, public ?User $user = null)
    {
    }

    /**
     * Execute the job.
     *
     * @throws PendingDeploymentException
     */
    public function handle(): void
    {
        $this->site->deploy([], $this->user);
    }
}
