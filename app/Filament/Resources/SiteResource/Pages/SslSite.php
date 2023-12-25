<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Traits\BreadcrumbTrait;
use Filament\Resources\Pages\EditRecord;

class SslSite extends EditRecord
{
    use BreadcrumbTrait;

    protected static string $resource = SiteResource::class;

    protected static ?string $title = 'SSL';

    protected static ?string $navigationIcon = 'heroicon-s-lock-closed';
}
