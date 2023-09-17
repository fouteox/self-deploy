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

    //    public function getBreadcrumbs(): array
    //    {
    //        $parentBreadcrumbs = parent::getBreadcrumbs();
    //
    //        reset($parentBreadcrumbs);
    //        $sitesName = array_shift($parentBreadcrumbs);
    //
    //        $newSitesUrl = SiteResource::getUrl('view', ['record' => static::getRecord()->id]);
    //
    //        $breadcrumbs = [
    //            ServerResource::getUrl('sites', ['record' => static::getRecord()->server]) => static::getRecord()->server->name,
    //            $newSitesUrl => $sitesName,
    //        ] + $parentBreadcrumbs;
    //
    //        return $breadcrumbs;
    //    }
}
