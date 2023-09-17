<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServerResource\Pages;
use App\FilamentPageSidebar\FilamentPageSideBar;
use App\Infrastructure\Entities\ServerStatus;
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
use Illuminate\Support\Str;

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
            ->wireNavigate()
            ->setNavigationItems([
                //                PageNavigationItem::make('Overview')
                //                    ->url(static::getUrl('view', ['record' => $record]))
                //                    ->icon('heroicon-s-eye')
                //                    ->isActiveWhen(function () {
                //                        return request()->routeIs(static::getRouteBaseName().'.view');
                //                    }),
                PageNavigationItem::make('Sites')
                    ->url(static::getUrl('sites', ['record' => $record]))
                    ->icon('heroicon-s-globe-alt')
                    ->isActiveWhen(function () {
                        return Str::startsWith(request()->route()->getName(), static::getRouteBaseName().'.sites');
                    }),
                PageNavigationItem::make('Databases')
                    ->url(static::getUrl('databases', ['record' => $record]))
                    ->icon('heroicon-s-circle-stack')
                    ->isActiveWhen(function () {
                        return request()->routeIs(static::getRouteBaseName().'.databases');
                    }),
                PageNavigationItem::make('Cronjobs')
                    ->url(static::getUrl('cronjobs', ['record' => $record]))
                    ->icon('heroicon-s-clock')
                    ->isActiveWhen(function () {
                        return request()->routeIs(static::getRouteBaseName().'.cronjobs');
                    }),
                PageNavigationItem::make('Daemons')
                    ->url(static::getUrl('daemons', ['record' => $record]))
                    ->icon('heroicon-s-wrench-screwdriver')
                    ->isActiveWhen(function () {
                        return request()->routeIs(static::getRouteBaseName().'.daemons');
                    }),
                PageNavigationItem::make('Firewall Rules')
                    ->url(static::getUrl('firewall-rules', ['record' => $record]))
                    ->icon('heroicon-s-shield-check')
                    ->isActiveWhen(function () {
                        return request()->routeIs(static::getRouteBaseName().'.firewall-rules');
                    }),
                PageNavigationItem::make('Backups')
                    ->url(static::getUrl('backups', ['record' => $record]))
                    ->icon('heroicon-s-rectangle-stack')
                    ->isActiveWhen(function () {
                        return request()->routeIs(static::getRouteBaseName().'.backups');
                    }),
                PageNavigationItem::make('Software')
                    ->url(static::getUrl('software', ['record' => $record]))
                    ->icon('heroicon-s-code-bracket')
                    ->isActiveWhen(function () {
                        return request()->routeIs(static::getRouteBaseName().'.software');
                    }),
                PageNavigationItem::make('Files')
                    ->url(static::getUrl('files', ['record' => $record]))
                    ->icon('heroicon-s-document-text')
                    ->isActiveWhen(function () {
                        return request()->routeIs(static::getRouteBaseName().'.files');
                    }),
                PageNavigationItem::make('Logs')
                    ->url(static::getUrl('logs', ['record' => $record]))
                    ->icon('heroicon-s-book-open')
                    ->isActiveWhen(function () {
                        return request()->routeIs(static::getRouteBaseName().'.logs');
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
                    ->badge()
                    ->color(fn (ServerStatus $state): string => match ($state->value) {
                        'new' => 'primary',
                        'starting', 'provisioning' => 'info',
                        'running' => 'success',
                        'paused' => 'warning',
                        'stopped', 'deleting' => 'danger',
                        'archived', 'unknown' => 'gray',
                    })
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
                fn (Model $record): string => ! $record->provisioned_at ? static::getUrl('provisioning', ['record' => $record]) : static::getUrl('sites', ['record' => $record])
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
            'create' => Pages\CreateServer::route('/create'),
            //            'view' => Pages\ViewServer::route('/{record}'),
            'edit' => Pages\EditServer::route('/{record}/edit'),
            'provisioning' => Pages\ServerProvisioning::route('/{record}/provisioning'),
            'sites' => Pages\ListSitesServer::route('/{record}/sites'),
            'sites/create' => Pages\CreateSiteServer::route('/{record}/sites/create'),
            'databases' => Pages\DatabaseServer::route('/{record}/databases'),
            'cronjobs' => Pages\CronServer::route('/{record}/cronjobs'),
            'daemons' => Pages\DaemonServer::route('/{record}/daemons'),
            'firewall-rules' => Pages\FirewallRulesServer::route('/{record}/firewall-rules'),
            'backups' => Pages\BackupServer::route('/{record}/backups'),
            'software' => Pages\SoftwareServer::route('/{record}/software'),
            'files' => Pages\FileServer::route('/{record}/files'),
            'logs' => Pages\LogServer::route('/{record}/logs'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return Auth::user()->currentTeam->servers()->getQuery();
    }

    public static function getBreadcrumbs(Model $record): array
    {
        $parentBreadcrumb = parent::getBreadcrumb();

        return [
            self::getUrl() => $parentBreadcrumb,
            ServerResource::getUrl('sites', ['record' => $record]) => $record->name,
            //            ...$parentBreadcrumbs,
        ];
    }
}
