<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServerResource\Pages;
use App\Infrastructure\Entities\ServerStatus;
use App\Models\Server;
use App\Models\SshKey;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Exists;

class ServerResource extends Resource
{
    protected static ?string $model = Server::class;

    protected static ?string $navigationIcon = 'heroicon-s-server';

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ListSitesServer::class,
            Pages\DatabaseServer::class,
            Pages\CronServer::class,
            Pages\DaemonServer::class,
            Pages\FirewallRulesServer::class,
            Pages\BackupServer::class,
            Pages\SoftwareServer::class,
            Pages\FileServer::class,
            Pages\LogServer::class,
            Pages\EditServer::class,
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
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
                        Forms\Components\Select::make('ssh_key')
                            ->label(__('SSH Keys'))
                            ->multiple()
                            ->options(auth()->user()->sshKeys()->pluck('name', 'id'))
                            ->exists(
                                table: SshKey::class,
                                column: 'id',
                                modifyRuleUsing: fn (Exists $rule) => $rule->where('user_id', auth()->id())
                            )
                            ->visibleOn('create'),
                    ]),
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
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->recordUrl(
                fn (Model $record): string => ! $record->provisioned_at ? static::getUrl('provisioning', ['record' => $record]) : static::getUrl('sites', ['record' => $record])
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServers::route('/'),
            'create' => Pages\CreateServer::route('/create'),
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
            self::getUrl('sites', ['record' => $record]) => $record->name,
        ];
    }
}
