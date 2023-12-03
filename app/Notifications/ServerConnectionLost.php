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

class ServerConnectionLost extends Notification implements ShouldQueue
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
            ->danger()
            ->title(__('Server connection lost'))
            ->body(__('We could not connect to your server \':server\' while performing the following action', [
                'server' => $this->server->name,
            ])."\n\n".$this->reference."\n\n".__('Please check your server\'s connection details and try again.'))
            ->getBroadcastMessage();
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject(__('Server connection lost'))
            ->line(__("We could not connect to your server ':server' while performing the following action", [
                'server' => $this->server->name,
            ]))
            ->line(Markdown::parse($this->reference))
            ->line(__('Please check your server\'s connection details and try again.'))
            ->action(__('View Server'), ServerResource::getUrl('sites', ['record' => $this->server->id]));
    }
}
