<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Traits\BreadcrumbTrait;
use Filament\Resources\Pages\ViewRecord;

class ViewSite extends ViewRecord
{
    use BreadcrumbTrait;

    protected static string $resource = SiteResource::class;

    protected static ?string $title = 'Overview';
}
