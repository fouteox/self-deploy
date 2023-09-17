<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Traits\BreadcrumbTrait;
use AymanAlhattami\FilamentPageWithSidebar\Traits\HasPageSidebar;
use Filament\Resources\Pages\ViewRecord;

class ViewSite extends ViewRecord
{
    use BreadcrumbTrait, HasPageSidebar;

    protected static string $resource = SiteResource::class;
}
