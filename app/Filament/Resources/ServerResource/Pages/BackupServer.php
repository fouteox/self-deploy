<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Traits\BreadcrumbTrait;
use App\Traits\RedirectsIfProvisioned;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BackupServer extends ManageRelatedRecords
{
    use BreadcrumbTrait, RedirectsIfProvisioned;

    protected static string $resource = ServerResource::class;

    protected static string $relationship = 'backups';

    protected static ?string $title = 'Backups';

    protected static ?string $navigationIcon = 'heroicon-s-rectangle-stack';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('server'))
            ->recordTitleAttribute('name')
            ->columns([
                // TODO: add backup
            ]);
    }
}
