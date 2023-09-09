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

    public Server $record;

    public function mount(Server $record): void
    {
        $this->record = $record;
        static::$view = static::getViewBasedOnServer($record);
    }

    public static function getViewBasedOnServer(Server $server): string
    {
        return $server->provisioned_at
            ? 'filament.resources.server-resource.pages.dashboard'
            : 'filament.resources.server-resource.pages.server-provisioning';
    }

    public function getHeading(): string
    {
        return $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('delete')
                ->label('Delete Server')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->provisioned_at === null)
                ->modalDescription('Deleting a server will remove all settings. We will delete it for you, but you might have to manually remove it from your provider.')
                ->modalIcon('heroicon-o-trash')
                ->action(fn () => $this->record->delete())
                ->after(fn () => $this->redirect(route('filament.admin.resources.servers.index'))),
        ];
    }
}
