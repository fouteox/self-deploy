<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Models\Site;
use App\Traits\BreadcrumbTrait;
use AymanAlhattami\FilamentPageWithSidebar\Traits\HasPageSidebar;
use Filament\Resources\Pages\Page;

class DeploymentsSite extends Page
{
    use BreadcrumbTrait, HasPageSidebar;

    protected static string $resource = SiteResource::class;

    protected static string $view = 'filament.resources.site-resource.pages.deployments-site';

    protected static ?string $title = 'Deployments';

    public Site $record;
}
