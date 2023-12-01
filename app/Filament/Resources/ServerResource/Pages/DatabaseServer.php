<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Filament\Resources\SiteResource;
use App\Traits\BreadcrumbTrait;
use App\Traits\RedirectsIfProvisioned;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DatabaseServer extends ManageRelatedRecords
{
    use BreadcrumbTrait, RedirectsIfProvisioned;

    protected static string $resource = ServerResource::class;

    protected static string $relationship = 'databases';

    protected static ?string $title = 'Databases';

    protected static ?string $navigationIcon = 'heroicon-s-circle-stack';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('status'),
                TextColumn::make('server.name'),
            ])
            ->filters([
                // ...
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // ...
            ])
            ->bulkActions([
                // ...
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->url(fn (): string => ServerResource::getUrl('sites/create', ['record' => $this->record])),
            ])
            ->recordUrl(fn (Model $site) => SiteResource::getUrl('view', ['record' => $site]));
    }
}
