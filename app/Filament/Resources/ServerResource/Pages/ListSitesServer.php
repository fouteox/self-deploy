<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Models\Server;
use App\Traits\RedirectsIfProvisioned;
use AymanAlhattami\FilamentPageWithSidebar\Traits\HasPageSidebar;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ListSitesServer extends Page implements HasTable
{
    use HasPageSidebar, InteractsWithTable, RedirectsIfProvisioned;

    protected static string $resource = ServerResource::class;

    protected static string $view = 'filament.resources.server-resource.pages.list-sites-server';

    protected static ?string $title = 'Sites';

    public Server $record;

    public function table(Table $table): Table
    {
        return $table
            ->relationship(fn (): HasMany => $this->record->sites())
            ->inverseRelationship('server')
            ->columns([
                TextColumn::make('repository_url'),
            ])
            ->filters([
                // ...
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
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->url(fn (): string => ServerResource::getUrl('sites/create', ['record' => $this->record])),
        ];
    }
}
