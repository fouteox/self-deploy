<?php

namespace App\Jobs;

use App\Models\Site;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class InstallWordpressCron implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public Site $site)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $server = $this->site->server;

        $cron = $server->crons()->create([
            'command' => $this->site->php_version->getBinary().' '.$this->site->getWebDirectory().'/wp-cron.php',
            'user' => $this->site->user,
            'expression' => '* * * * *',
        ]);

        dispatch(new InstallCron($cron));
    }
}
