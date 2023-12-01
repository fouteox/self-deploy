<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Traits\BreadcrumbTrait;
use App\Traits\RedirectsIfProvisioned;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class FileServer extends Page
{
    use BreadcrumbTrait, InteractsWithRecord, RedirectsIfProvisioned {
        BreadcrumbTrait::getBreadcrumbs insteadof InteractsWithRecord;
    }

    protected static string $resource = ServerResource::class;

    protected static string $view = 'filament.resources.server-resource.pages.file-server';

    protected static ?string $title = 'Files';

    protected static ?string $navigationIcon = 'heroicon-s-document-text';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        //        $user = auth()->user();
        //        $user->notify(Notification::make()->warning()->title('Test de notification')->toBroadcast());

        static::authorizeResourceAccess();
    }
}
