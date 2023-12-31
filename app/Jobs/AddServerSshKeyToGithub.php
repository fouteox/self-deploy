<?php

namespace App\Jobs;

use App\Models\Credential;
use App\Models\Server;
use App\SourceControl\ProviderFactory;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AddServerSshKeyToGithub implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public Server $server, public Credential $githubCredentials)
    {
    }

    /**
     * Execute the job.
     *
     * @throws Exception
     */
    public function handle(ProviderFactory $providerFactory): void
    {
        $github = $providerFactory->forCredentials($this->githubCredentials);

        $appName = config('app.name');

        $github->addKey(
            "{$this->server->name} (added by $appName)",
            $this->server->user_public_key
        );
    }
}
