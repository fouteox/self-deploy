<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Traits\BreadcrumbTrait;
use Filament\Resources\Pages\EditRecord;

class DeploymentSettingsSite extends EditRecord
{
    use BreadcrumbTrait;

    protected static string $resource = SiteResource::class;

    protected static ?string $title = 'Deployments Settings';

    protected static ?string $navigationIcon = 'heroicon-s-wrench-screwdriver';

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
        ];
    }
}
