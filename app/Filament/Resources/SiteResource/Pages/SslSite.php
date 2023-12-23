<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use Filament\Resources\Pages\EditRecord;

class SslSite extends EditRecord
{
    protected static string $resource = SiteResource::class;

    protected static ?string $title = 'SSL';

    protected static ?string $navigationIcon = 'heroicon-s-lock-closed';
}
