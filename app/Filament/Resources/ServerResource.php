<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServerResource\Pages;
use App\FilamentPageSidebar\FilamentPageSideBar;
use App\Models\Server;
use AymanAlhattami\FilamentPageWithSidebar\PageNavigationItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ServerResource extends Resource
{
    protected static ?string $model = Server::class;

    protected static ?string $navigationIcon = 'heroicon-s-server';

    public static function sidebar(Model $record): FilamentPageSidebar
    {
        return FilamentPageSidebar::make()
            ->setTitle($record->name)
            ->setDescription($record->public_ipv4)
            ->setDescriptionCopyable(true)
            ->setNavigationItems([
                PageNavigationItem::make('Overview')
                    ->url(static::getUrl('view', ['record' => $record]))
                    ->icon('heroicon-s-eye')
                    ->isActiveWhen(function () {
                        return request()->routeIs(static::getRouteBaseName().'.view');
                    }),
                PageNavigationItem::make('Sites')
                    ->url(static::getUrl('sites', ['record' => $record]))
                    ->icon('heroicon-s-globe-alt')
                    ->isActiveWhen(function () {
                        return request()->routeIs(static::getRouteBaseName().'.sites');
                    }),
                PageNavigationItem::make('Manage')
                    ->url(static::getUrl('edit', ['record' => $record]))
                    ->icon('heroicon-s-cog-6-tooth')
                    ->isActiveWhen(function () {
                        return request()->routeIs(static::getRouteBaseName().'.edit');
                    }),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('public_ipv4')
                    ->required()
                    ->ipv4(),
                Forms\Components\TextInput::make('ssh_port')
                    ->required()
                    ->rules(['integer', 'min:1', 'max:65535']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('provider')->state(fn (Model $record) => $record->provider->getDisplayName()),
                Tables\Columns\TextColumn::make('public_ipv4')
                    ->label('IP Address'),
                Tables\Columns\TextColumn::make('status')
                    ->alignEnd(),
            ])
            ->filters([
                //
            ])
            ->actions([
                //                Tables\Actions\ViewAction::make()
                //                    ->url(fn (Model $record): string => static::getUrl('custom', ['record' => $record])),
                //                    ->visible(fn (Model $record) => $record->provisioned_at !== null),
                //                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->recordUrl(
                fn (Model $record): string => ! $record->provisioned_at ? static::getUrl('provisioning', ['record' => $record]) : static::getUrl('view', ['record' => $record])
            );
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServers::route('/'),
            'view' => Pages\ViewServer::route('/{record}'),
            'create' => Pages\CreateServer::route('/create'),
            'edit' => Pages\EditServer::route('/{record}/edit'),
            'provisioning' => Pages\ServerProvisioning::route('/{record}/provisioning'),
            'sites' => Pages\ListSitesServer::route('/{record}/sites'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return Auth::user()->currentTeam->servers()->getQuery();
    }
}
