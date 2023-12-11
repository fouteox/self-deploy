<?php

namespace App\Filament\Resources\SshKeyResource\Pages;

use App\Filament\Resources\SshKeyResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSshKeys extends ManageRecords
{
    protected static string $resource = SshKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->mutateFormDataUsing(function ($data) {
                $data['user_id'] = auth()->id();

                return $data;
            }),
        ];
    }
}
