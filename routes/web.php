<?php

use App\Http\Controllers\ServerProvisionScriptController;
use App\Http\Controllers\SiteDeploymentController;
use App\Http\Controllers\TaskWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::any('/deploy/{site}/{token}', [SiteDeploymentController::class, 'deployWithToken'])->name('site.deploy-with-token');

Route::middleware('signed:relative')->group(function () {
    // Backups...
    //    Route::get('/backup-job/{backup_job}', [BackupJobController::class, 'show'])->name('backup-job.show');
    //    Route::patch('/backup-job/{backup_job}', [BackupJobController::class, 'update'])->name('backup-job.update');

    // Provision Script for Custom Servers...
    Route::get('/servers/{server}/provision-script', ServerProvisionScriptController::class)->name('servers.provision-script');

    // Tasks...
    Route::post('/webhook/task/{task}/timeout', [TaskWebhookController::class, 'markAsTimedOut'])->name('webhook.task.mark-as-timed-out');
    Route::post('/webhook/task/{task}/failed', [TaskWebhookController::class, 'markAsFailed'])->name('webhook.task.mark-as-failed');
    Route::post('/webhook/task/{task}/finished', [TaskWebhookController::class, 'markAsFinished'])->name('webhook.task.mark-as-finished');
    Route::post('/webhook/task/{task}/callback', [TaskWebhookController::class, 'callback'])->name('webhook.task.callback');
});
