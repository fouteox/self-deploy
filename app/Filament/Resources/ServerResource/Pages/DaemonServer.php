<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Enum;
use App\Filament\Resources\ServerResource;
use App\Jobs\InstallDaemon;
use App\Jobs\UninstallDaemon;
use App\Models\ActivityLog;
use App\Models\Daemon;
use App\Models\Server;
use App\Signal;
use App\Traits\BreadcrumbTrait;
use App\Traits\RedirectsIfProvisioned;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/* @method Server getRecord() */
class DaemonServer extends ManageRelatedRecords
{
    use BreadcrumbTrait, RedirectsIfProvisioned;

    protected static string $resource = ServerResource::class;

    protected static string $relationship = 'daemons';

    protected static ?string $title = 'Daemons';

    protected static ?string $navigationIcon = 'heroicon-s-wrench-screwdriver';

    public function getListeners(): array
    {
        return [
            'echo-private:teams.'.auth()->user()->current_team_id.',DaemonDeleted' => 'refreshComponent',
            'echo-private:teams.'.auth()->user()->current_team_id.',DaemonUpdated' => 'refreshComponent',
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
                TextColumn::make('processes'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(function (Daemon $record): void {
                        ActivityLog::create([
                            'team_id' => auth()->user()->current_team_id,
                            'user_id' => auth()->user()->id,
                            'subject_id' => $record->getKey(),
                            'subject_type' => $record->getMorphClass(),
                            'description' => __("Created daemon ':command' on server ':server'", ['command' => $record->command, 'server' => $record->server->name]),
                        ]);
                    })
                    ->successNotificationTitle(__('The Daemon has been created and will be installed on the server.')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->using(function (Daemon $record, array $data): Daemon {
                        $record->forceFill(['installed_at' => null])->update($data);

                        ActivityLog::create([
                            'team_id' => auth()->user()->current_team_id,
                            'user_id' => auth()->user()->id,
                            'subject_id' => $record->getKey(),
                            'subject_type' => $record->getMorphClass(),
                            'description' => __("Updated daemon ':command' on server ':server'", ['command' => $record->command, 'server' => $record->server->name]),
                        ]);

                        dispatch(new InstallDaemon($record, auth()->user()));

                        return $record;
                    })
                    ->successNotificationTitle(__('The Daemon will be updated on the server.')),
                Tables\Actions\DeleteAction::make()
                    ->using(function (Daemon $record): void {
                        $record->markUninstallationRequest();

                        dispatch(new UninstallDaemon($record, auth()->user()));

                        ActivityLog::create([
                            'team_id' => auth()->user()->current_team_id,
                            'user_id' => auth()->user()->id,
                            'subject_id' => $record->getKey(),
                            'subject_type' => $record->getMorphClass(),
                            'description' => __("Deleted daemon ':command' from server ':server'", ['command' => $record->command, 'server' => $record->server->name]),
                        ]);

                        Notification::make()
                            ->title(__('The Daemon will be uninstalled from the server.'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('command')
                    ->required()
                    ->maxLength(255),
                TextInput::make('directory')
                    ->maxLength(255)
                    ->placeholder('/home/eddy/site.com/current'),
                Select::make('user')
                    ->options([
                        'root' => 'root',
                        $this->getRecord()->username => $this->getRecord()->username,
                    ])
                    ->default($this->getRecord()->username)
                    ->required()
                    ->in(['root', $this->getRecord()->username]),
                TextInput::make('processes')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->default(1),
                TextInput::make('stop_wait_seconds')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->default(10),
                Select::make('stop_signal')
                    ->options($this->signalOptions())
                    ->default(Signal::TERM)
                    ->required()
                    ->enum(Signal::class),
            ]);
    }

    private function signalOptions(): array
    {
        return Enum::options(Signal::class, true);
    }
}
