<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteResource\Pages;
use App\Models\ActivityLog;
use App\Models\Site;
use Filament\Forms\Form;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static ?string $navigationIcon = 'heroicon-s-globe-alt';

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewSite::class,
            Pages\DeploymentsSite::class,
            Pages\DeploymentSettingsSite::class,
            Pages\SslSite::class,
            Pages\FileSite::class,
            Pages\LogSite::class,
            Pages\EditSite::class,
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
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->recordUrl(fn (Site $site): string => static::getUrl('view', ['record' => $site]));
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make(__('Site Overview'))
                    ->schema([
                        TextEntry::make('address')
                            ->url(fn (Site $record): string => $record->url)
                            ->openUrlInNewTab()
                            ->icon('heroicon-s-arrow-right-circle')
                            ->iconPosition(IconPosition::After),
                        TextEntry::make('server.name')
                            ->url(fn (Site $record): string => ServerResource::getUrl('sites', ['record' => $record->server])),
                        TextEntry::make('path')
                            ->copyable()
                            ->icon('heroicon-s-clipboard-document')
                            ->iconPosition(IconPosition::After)
                            ->columnSpanFull(),
                        TextEntry::make('php_version')
                            ->formatStateUsing(fn ($state): string => $state->getDisplayName()),
                        TextEntry::make('type')
                            ->formatStateUsing(fn ($state): string => $state->getDisplayName()),
                        TextEntry::make('repository')
                            ->state(fn (Site $record): string => "$record->repository_url ($record->repository_branch)"),
                    ])
                    ->inlineLabel(),
                Section::make(__('Deployment'))
                    ->schema([
                        IconEntry::make('zero_downtime_deployment')
                            ->boolean(),
                        TextEntry::make('latestDeployment.updated_at'),
                        TextEntry::make('deploy_url')
                            ->label(__('Deploy URL'))
                            ->state(fn (Site $record): string => route('site.deploy-with-token', [$record, $record->deploy_token]))
                            ->suffixAction(
                                Action::make('refreshToken')
                                    ->icon('heroicon-s-arrow-path')
                                    ->requiresConfirmation()
                                    ->modalHeading(__('Are you sure you want to regenerate the deploy token?'))
                                    ->modalDescription(__('This will invalidate the current deploy token.'))
                                    ->action(function (Site $record) {
                                        $record->deploy_token = Str::random(32);
                                        $record->save();

                                        ActivityLog::create([
                                            'team_id' => auth()->user()->current_team_id,
                                            'user_id' => auth()->id(),
                                            'subject_id' => $record->getKey(),
                                            'subject_type' => $record->getMorphClass(),
                                            'description' => __("Updated deploy token of site ':address' on server ':server'", ['address' => $record->address, 'server' => $record->server->name]),
                                        ]);

                                        Notification::make()
                                            ->title(__('The deploy token has been regenerated.'))
                                            ->success()
                                            ->send();
                                    })
                            ),
                    ])
                    ->inlineLabel(),
                Section::make(__('Delete Site'))
                    ->schema([
                        TextEntry::make('delete')
                            ->default(__('Deleting a site will remove all files associated with it. This action cannot be undone.'))
                            ->hiddenLabel(),
                        Actions::make([
                            Action::make('delete')
                                ->label(__('Delete Site'))
                                ->color('danger'),
                            // TODO : add action to delete action site
                        ]),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSites::route('/'),
            'view' => Pages\ViewSite::route('/{record}'),
            'create' => Pages\CreateSite::route('/create'),
            'edit' => Pages\EditSite::route('/{record}/edit'),
            'deployments_site' => Pages\DeploymentsSite::route('/{record}/deployments'),
            'deployments_settings' => Pages\DeploymentSettingsSite::route('/{record}/deployments-settings'),
            'ssl' => Pages\SslSite::route('/{record}/ssl'),
            'files' => Pages\FileSite::route('/{record}/files'),
            'logs' => Pages\LogSite::route('/{record}/logs'),
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
