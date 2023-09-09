<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Models\Server;
use App\Traits\RedirectsIfProvisioned;
use AymanAlhattami\FilamentPageWithSidebar\Traits\HasPageSidebar;
use Filament\Resources\Pages\Page;

class FileServer extends Page
{
    use HasPageSidebar, RedirectsIfProvisioned;

    protected static string $resource = ServerResource::class;

    protected static string $view = 'filament.resources.server-resource.pages.file-server';

    public Server $record;
}
