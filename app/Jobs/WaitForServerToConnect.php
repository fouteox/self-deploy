<?php

namespace App\Jobs;

use App\Infrastructure\Entities\ServerStatus;
use App\Models\Server;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WaitForServerToConnect implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 40;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public Server $server)
    {
    }

    /**
     * Execute the job.
     *
     * @throws Exception
     */
    public function handle(): bool
    {
        if (! $this->server->public_ipv4) {
            $ip = $this->server->getProvider()->getPublicIpv4OfServer($this->server->provider_id);

            if (! $ip) {
                $this->release(15);

                return false;
            }

            $this->server->forceFill(['public_ipv4' => $ip])->save();
        }

        if (! $this->server->canConnectOverSsh()) {
            $this->release(15);

            return false;
        }

        if ($this->server->status === ServerStatus::Starting) {
            $this->server->forceFill(['status' => ServerStatus::Running])->save();
        }

        return true;
    }

    /**
     * Handle a job failure.
     */
    public function failed(): void
    {
        dispatch(new CleanupFailedServerProvisioning($this->server));
    }
}
