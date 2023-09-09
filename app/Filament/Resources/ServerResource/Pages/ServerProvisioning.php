<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Models\Server;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class ServerProvisioning extends Page
{
    protected static string $resource = ServerResource::class;

    protected static string $view = 'filament.resources.server-resource.pages.server-provisioning';

    public Server $server;

    public function mount(Server $server): void
    {
        $this->server = $server;
        static::$view = static::getViewBasedOnServer($server);
    }

    public static function getViewBasedOnServer(Server $server): string
    {
        return $server->provisioned_at
            ? 'filament.resources.server-resource.pages.dashboard'
            : 'filament.resources.server-resource.pages.server-provisioning';
    }

    public function getHeading(): string
    {
        return $this->server->name;
    }

    public function getSubheading(): string
    {
        return $this->server->public_ipv4;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('delete')
                ->label('Delete Server')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => $this->server->provisioned_at === null)
                ->modalDescription('Deleting a server will remove all settings. We will delete it for you, but you might have to manually remove it from your provider.')
                ->modalIcon('heroicon-o-trash')
                ->action(fn () => $this->server->delete())
                ->after(fn () => $this->redirect(route('filament.admin.resources.servers.index'))),
        ];
    }
}
