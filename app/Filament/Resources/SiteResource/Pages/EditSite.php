<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Traits\BreadcrumbTrait;
use Filament\Resources\Pages\EditRecord;

class EditSite extends EditRecord
{
    use BreadcrumbTrait;

    protected static string $resource = SiteResource::class;

    protected static ?string $title = 'Settings';

    protected static ?string $navigationIcon = 'heroicon-s-cog-6-tooth';

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
        ];
    }
}
