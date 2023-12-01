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
use Illuminate\Database\Eloquent\Relations\HasMany;

class ListSitesServer extends ManageRelatedRecords
{
    use BreadcrumbTrait, RedirectsIfProvisioned;

    protected static string $resource = ServerResource::class;

    protected static string $relationship = 'sites';

    protected static ?string $title = 'Sites';

    protected static ?string $navigationIcon = 'heroicon-s-globe-alt';

    public function table(Table $table): Table
    {
        return $table
            ->relationship(fn (): HasMany => $this->record->sites())
            ->inverseRelationship('server')
            ->columns([
                TextColumn::make('address'),
                TextColumn::make('php_version'),
                TextColumn::make('created_at'),
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
