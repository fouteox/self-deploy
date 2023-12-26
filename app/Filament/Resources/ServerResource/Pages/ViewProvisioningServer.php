<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Infrastructure\Entities\ServerStatus;
use App\Jobs\DeleteServerFromInfrastructure;
use App\Models\Server;
use App\Provider;
use App\Services\StepsServerProvisioning;
use App\Traits\HandlesUserContext;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

/* @method Server getRecord() */
class ViewProvisioningServer extends ViewRecord
{
    use HandlesUserContext;

    protected static string $resource = ServerResource::class;

    public function getHeading(): string
    {
        return $this->getRecord()->name;
    }

    public function getListeners(): array
    {
        return [
            'echo-private:teams.'.$this->team()->id.',ServerUpdated' => 'refresh',
            'echo-private:teams.'.$this->team()->id.',ServerDeleted' => 'refresh',
        ];
    }

    public function refresh(): void
    {
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Step')
                    ->heading(fn (Server $record): string => StepsServerProvisioning::countSteps($record))
                    ->schema(function (Server $record) {
                        return array_map(function ($key, $step) {
                            return ViewEntry::make("step-$key")
                                ->view('filament.infolists.entries.status-provisioning')
                                ->hiddenLabel()
                                ->state($step);
                        }, array_keys(StepsServerProvisioning::allSteps($record)), StepsServerProvisioning::allSteps($record));
                    })
                    ->columnSpan(['md' => 2])
                    ->columnStart(['md' => 1]),
                Section::make(__('Server Provisioning'))
                    ->columnSpan(['md' => 1])
                    ->schema([
                        TextEntry::make('info')
                            ->hiddenLabel()
                            ->state(fn (Server $record): string => match ($record->status) {
                                ServerStatus::Provisioning => __('The server is currently being provisioned.'),
                                ServerStatus::Starting => __('The server is created at the provider and is currently starting up.'),
                                default => __('The server is currently being created at the provider.')
                            }),
                        TextEntry::make('info_refresh')
                            ->hiddenLabel()
                            ->state(fn (): string => __('This page will automatically refresh on updates.')),
                        TextEntry::make('see_provisioning_script')
                            ->hiddenLabel()
                            ->state(function () {
                                return __('Need to see the provisioning script again?');
                            })
                            ->visible(fn (Server $record): bool => $record->provider === Provider::CustomServer && $record->status !== ServerStatus::Provisioning),
                        Actions::make([
                            Actions\Action::make('view_provisioning_script')
                                ->label(__('View Provisioning Script'))
                                ->fillForm(fn (Server $record): array => [
                                    'script' => $record->provisionCommand(),
                                ])
                                ->form([
                                    TextInput::make('script')
                                        ->hiddenLabel()
                                        ->disabled()
                                        ->extraAttributes(function ($state) {
                                            return [
                                                'x-on:click' => 'window.navigator.clipboard.writeText("'.$state.'"); $tooltip("Copied to clipboard", { timeout: 1500 });',
                                            ];
                                        })
                                        ->suffixAction(
                                            \Filament\Forms\Components\Actions\Action::make('copy')
                                                ->icon('heroicon-m-clipboard')
                                        ),
                                ])
                                ->modalHeading(__('Provision Command'))
                                ->modalDescription(__('Run this script as root on your server to start the provisioning process:'))
                                ->modalSubmitAction(false)
                                ->modalCancelAction(false)
                                ->visible(fn (Server $record): bool => $record->provider === Provider::CustomServer && $record->status !== ServerStatus::Provisioning),
                        ]),
                    ]),
            ])
            ->columns(['md' => 3, 'lg' => 3]);
    }

    public function getBreadcrumbs(): array
    {

        return [
            ServerResource::getUrl() => 'Servers',
            'Provisioning',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('delete')
                ->label('Delete Server')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => $this->getRecord()->provisioned_at === null)
                ->modalDescription('Deleting a server will remove all settings. We will delete it for you, but you might have to manually remove it from your provider.')
                ->modalIcon('heroicon-o-trash')
                ->action(function (): void {
                    $this->getRecord()->forceFill([
                        'status' => ServerStatus::Deleting,
                        'uninstallation_requested_at' => now(),
                    ])->save();

                    dispatch(new DeleteServerFromInfrastructure($this->getRecord(), $this->user()));

                    $this->logActivity(__("Deleted server ':server'", ['server' => $this->getRecord()->name]), $this->getRecord());

                    $this->sendNotification(__('Your server is being deleted.'));

                    $this->redirect(
                        ServerResource::getUrl(),
                        navigate: true
                    );
                }),
        ];
    }
}
