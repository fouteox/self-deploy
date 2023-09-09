<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Traits\RedirectsIfProvisioned;
use AymanAlhattami\FilamentPageWithSidebar\Traits\HasPageSidebar;
use Filament\Resources\Pages\ViewRecord;

class ViewServer extends ViewRecord
{
    use HasPageSidebar, RedirectsIfProvisioned;

    protected static string $resource = ServerResource::class;
}
