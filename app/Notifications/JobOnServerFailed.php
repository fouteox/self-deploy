<?php

namespace App\Notifications;

use App\Filament\Resources\ServerResource;
use App\Models\Server;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JobOnServerFailed extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Server $server, public string $reference)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(): array
    {
        return ['broadcast', 'mail'];
    }

    public function toBroadcast(): BroadcastMessage
    {
        return FilamentNotification::make()
            ->title('Job on server failed')
            ->danger()
            ->body("We tried to run a job on your server, but it failed. Here's what we tried to do:\n\n".$this->reference)
            ->getBroadcastMessage();
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject(__('Job on server failed'))
            ->line(__("We tried to run a job on your server, but it failed. Here's what we tried to do:"))
            ->line(Markdown::parse($this->reference))
            ->action(__('View Server'), ServerResource::getUrl('sites', ['record' => $this->server->id]));
    }
}
