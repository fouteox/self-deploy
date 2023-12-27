<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Filament\Resources\SiteResource;
use App\Jobs\UninstallSite;
use App\Models\Site;
use App\Traits\BreadcrumbTrait;
use App\Traits\HandlesUserContext;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Str;
use Livewire\Component;

class ViewSite extends ViewRecord
{
    use BreadcrumbTrait, HandlesUserContext;

    protected static string $resource = SiteResource::class;

    protected static ?string $title = 'Overview';

    public function infolist(Infolist $infolist): Infolist
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
                        TextEntry::make('latestDeployment.updated_at')
                            ->dateTime(),
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

                                        $this->logActivity(
                                            __("Updated deploy token of site ':address' on server ':server'", ['address' => $record->address, 'server' => $record->server->name]),
                                            $record
                                        );

                                        $this->sendNotification(__('The deploy token has been regenerated.'));
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
                                ->color('danger')
                                ->requiresConfirmation()
                                ->action(function (Site $record, Component $livewire) {
                                    $record->delete();

                                    dispatch(new UninstallSite($record->server, $record->path));

                                    $this->logActivity(
                                        __("Deleted site ':address' from server ':server'", ['address' => $record->address, 'server' => $record->server->name]),
                                        $record
                                    );

                                    $this->sendNotification(__('The site is deleted and will be uninstalled from the server shortly.'));

                                    $livewire->redirect(ServerResource::getUrl('sites', ['record' => $record->server]), navigate: true);
                                }),
                        ]),
                    ]),
            ]);
    }
}
