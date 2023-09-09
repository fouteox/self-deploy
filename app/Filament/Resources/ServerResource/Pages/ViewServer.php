<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use AymanAlhattami\FilamentPageWithSidebar\Traits\HasPageSidebar;
use Filament\Resources\Pages\ViewRecord;

class ViewServer extends ViewRecord
{
    use HasPageSidebar;

    protected static string $resource = ServerResource::class;
}
