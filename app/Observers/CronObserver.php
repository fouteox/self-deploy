<?php

namespace App\Observers;

use App\Events\CronDeleted;
use App\Events\CronUpdated;
use App\Jobs\InstallCron;
use App\Models\Cron;

class CronObserver
{
    /**
     * Handle the Cron "created" event.
     */
    public function created(Cron $cron): void
    {
        InstallCron::dispatch($cron, auth()->user())->onQueue('commands');
    }

    /**
     * Handle the Cron "updated" event.
     */
    public function updated(Cron $cron): void
    {
        event(new CronUpdated($cron));
    }

    /**
     * Handle the Cron "deleted" event.
     */
    public function deleted(Cron $cron): void
    {
        event(new CronDeleted($cron->id, $cron->server->team_id));
    }
}
