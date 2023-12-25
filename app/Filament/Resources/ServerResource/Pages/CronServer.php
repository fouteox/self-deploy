<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Jobs\InstallCron;
use App\Jobs\UninstallCron;
use App\Models\Cron;
use App\Models\Server;
use App\Rules\CronExpression;
use App\Traits\BreadcrumbTrait;
use App\Traits\HandlesUserContext;
use App\Traits\RedirectsIfProvisioned;
use App\View\Components\StatusColumn;
use Filament\Forms;
use Filament\Forms\Components\Radio;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/* @method Server getRecord() */
class CronServer extends ManageRelatedRecords
{
    use BreadcrumbTrait, HandlesUserContext, RedirectsIfProvisioned;

    protected static string $resource = ServerResource::class;

    protected static string $relationship = 'crons';

    protected static ?string $title = 'Crons';

    protected static ?string $navigationIcon = 'heroicon-s-clock';

    public function getListeners(): array
    {
        return [
            'echo-private:teams.'.auth()->user()->current_team_id.',CronDeleted' => 'refreshComponent',
            'echo-private:teams.'.auth()->user()->current_team_id.',CronUpdated' => 'refreshComponent',
        ];
    }

    public function refreshComponent(): void
    {
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('server'))
            ->recordTitleAttribute('command')
            ->columns([
                TextColumn::make('command'),
                TextColumn::make('user'),
                TextColumn::make('expression')
                    ->label(__('Frequency'))
                    ->description(function (string $state): string {
                        $options = Cron::predefinedFrequencyOptions();

                        return $options[$state] ?? __('Custom expression');
                    }),
                TextColumn::make('status')
                    ->state(fn (Cron $record): string => StatusColumn::getStatus(record: $record)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        return $this->mutateCronFormData($data);
                    })
                    ->after(function (Cron $record): void {
                        $this->logActivity(__("Created cron ':command' on server ':server'", ['command' => $record->command, 'server' => $record->server->name]), $record);
                    })
                    ->successNotificationTitle(__('The Cron has been created and will be installed on the server.')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->using(function (Cron $record, array $data): Cron {
                        $record->forceFill([
                            'installed_at' => null,
                            'installation_failed_at' => null,
                            'uninstallation_failed_at' => null,
                        ])->update($this->mutateCronFormData($data));

                        $this->logActivity(__("Updated cron ':command' on server ':server'", ['command' => $record->command, 'server' => $record->server->name]), $record);

                        dispatch(new InstallCron($record, auth()->user()));

                        return $record;
                    })
                    ->successNotificationTitle(__('The Cron will be updated on the server.')),
                Tables\Actions\DeleteAction::make()
                    ->using(function (Cron $record): void {
                        $record->markUninstallationRequest();

                        dispatch(new UninstallCron($record, auth()->user()));

                        $this->logActivity(__("Deleted cron ':command' from server ':server'", ['command' => $record->command, 'server' => $record->server->name]), $record);

                        $this->sendNotification(__('The Cron will be uninstalled from the server.'));
                    }),
            ]);
    }

    private function mutateCronFormData(array $data): array
    {
        $data['expression'] = $data['expression'] ?? $data['frequency'] ?? null;
        unset($data['frequency']);

        return $data;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('command')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('php8.2 /home/eddy/site.com/current/artisan schedule:run'),
                Forms\Components\TextInput::make('user')
                    ->required()
                    ->maxLength(255)
                    ->in(['root', $this->getRecord()->username])
                    ->default($this->getRecord()->username),
                Forms\Components\Radio::make('frequency')
                    ->options(Cron::predefinedFrequencyOptions())
                    ->required()
                    ->in(array_keys(Cron::predefinedFrequencyOptions()))
                    ->live()
                    ->afterStateHydrated(function (Radio $component, ?Cron $record): void {
                        if ($record) {
                            $component->state($record->expression && array_key_exists($record->expression, Cron::predefinedFrequencyOptions()) ? $record->expression : 'custom');
                        }
                    }),
                Forms\Components\TextInput::make('expression')
                    ->visible(fn (Get $get): bool => $get('frequency') === 'custom')
                    ->requiredIf('frequency', 'custom')
                    ->rule(new CronExpression)
                    ->maxLength(255),
            ]);
    }
}
