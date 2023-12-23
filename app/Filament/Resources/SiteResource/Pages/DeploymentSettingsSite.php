<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use Filament\Resources\Pages\EditRecord;

class DeploymentSettingsSite extends EditRecord
{
    protected static string $resource = SiteResource::class;

    protected static ?string $title = 'Deployments Settings';

    protected static ?string $navigationIcon = 'heroicon-s-wrench-screwdriver';
}
