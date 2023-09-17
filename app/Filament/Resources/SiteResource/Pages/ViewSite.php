<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Filament\Resources\SiteResource;
use AymanAlhattami\FilamentPageWithSidebar\Traits\HasPageSidebar;
use Filament\Resources\Pages\ViewRecord;

class ViewSite extends ViewRecord
{
    use HasPageSidebar;

    protected static string $resource = SiteResource::class;

    public function getBreadcrumbs(): array
    {
        $parentBreadcrumbs = parent::getBreadcrumbs();

        reset($parentBreadcrumbs);
        $sitesName = array_shift($parentBreadcrumbs);

        $newSitesUrl = ServerResource::getUrl('sites', ['record' => static::getRecord()->server]);

        $breadcrumbs = [
            ServerResource::getUrl('view', ['record' => static::getRecord()->server]) => static::getRecord()->server->name,
            $newSitesUrl => $sitesName,
        ] + $parentBreadcrumbs;

        return $breadcrumbs;
    }
}
