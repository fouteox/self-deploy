<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Traits\BreadcrumbTrait;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServer extends EditRecord
{
    use BreadcrumbTrait;

    protected static string $resource = ServerResource::class;

    protected static ?string $title = 'Settings';

    protected static ?string $navigationIcon = 'heroicon-s-cog-6-tooth';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
