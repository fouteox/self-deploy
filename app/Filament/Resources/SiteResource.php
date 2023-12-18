<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteResource\Pages;
use App\Models\Site;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static ?string $navigationIcon = 'heroicon-s-globe-alt';

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewSite::class,
            Pages\EditSite::class,
            Pages\DeploymentsSite::class,
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('address'),
                TextColumn::make('server.name'),
                TextColumn::make('created_at'),
            ])
            ->filters([
                //
            ])
            ->actions([
                //                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->recordUrl(fn (Site $site): string => static::getUrl('view', ['record' => $site]));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSites::route('/'),
            'view' => Pages\ViewSite::route('/{record}'),
            'create' => Pages\CreateSite::route('/create'),
            'edit' => Pages\EditSite::route('/{record}/edit'),
            'deployments_site' => Pages\DeploymentsSite::route('/{record}/deployments'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('server')
            ->whereRelation('server', 'team_id', auth()->user()->currentTeam->id);
    }

    public static function getBreadcrumbs(Model $record): array
    {
        return [
            ServerResource::getUrl() => 'Servers',
            ServerResource::getUrl('sites', ['record' => $record->server]) => $record->server->name,
            self::getUrl('view', ['record' => $record]) => $record->address,
        ];
    }
}
