<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Jobs\MakeSoftwareDefaultOnServer;
use App\Jobs\RestartSoftwareOnServer;
use App\Models\Server;
use App\Models\Software;
use App\Server\SoftwareEnum;
use App\Traits\BreadcrumbTrait;
use App\Traits\HandlesUserContext;
use App\Traits\RedirectsIfProvisioned;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/* @method Server getRecord() */
class SoftwareServer extends Page implements HasTable
{
    use BreadcrumbTrait, HandlesUserContext, InteractsWithRecord, InteractsWithTable, RedirectsIfProvisioned {
        BreadcrumbTrait::getBreadcrumbs insteadof InteractsWithRecord;
    }

    protected static string $resource = ServerResource::class;

    protected static string $view = 'filament.resources.server-resource.pages.software-server';

    protected static ?string $title = 'Softwares';

    protected static ?string $navigationIcon = 'heroicon-s-code-bracket';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        static::authorizeResourceAccess();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Software::queryForSoftwares($this->getRecord()))
            ->columns([
                TextColumn::make('name'),
            ])
            ->actions([
                Action::make('make-default-cli')
                    ->label(__('Make CLI default'))
                    ->button()
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Software $record) {
                        $software = SoftwareEnum::from($record->id);

                        $this->softwareOperation(
                            $record->id,
                            new MakeSoftwareDefaultOnServer($this->getRecord(), $software),
                            __("Made ':software' the CLI default on server ':server'", ['software' => $software->getDisplayName(), 'server' => $this->getRecord()->name]),
                            __(':software will now be the CLI default on the server.', ['software' => $software->getDisplayName()])
                        );
                    })
                    ->visible(fn (Software $record): bool => $record->hasUpdateAlternativesTask),
                Action::make('restart')
                    ->label(__('Restart'))
                    ->button()
                    ->requiresConfirmation()
                    ->action(function (Software $record) {
                        $software = SoftwareEnum::from($record->id);

                        $this->softwareOperation(
                            $record->id,
                            new RestartSoftwareOnServer($this->getRecord(), $software),
                            __("Restarted ':software' on server ':server'", ['software' => $software->getDisplayName(), 'server' => $this->getRecord()->name]),
                            __(':software will be restarted on the server.', ['software' => $software->getDisplayName()])
                        );
                    })
                    ->visible(fn (Software $record): bool => $record->hasRestartTask),
            ])
            ->paginated(false);
    }

    private function softwareOperation(string $softwareId, $job, string $activityDescription, string $notificationTitle): void
    {
        dispatch($job);
        $this->logActivity($activityDescription);
        $this->sendNotification($notificationTitle);
        $this->dispatch('close-modal', id: $softwareId);
    }
}
