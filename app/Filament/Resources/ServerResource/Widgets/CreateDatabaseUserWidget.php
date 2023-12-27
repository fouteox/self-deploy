<?php

namespace App\Filament\Resources\ServerResource\Widgets;

use App\Jobs\InstallDatabaseUser;
use App\Jobs\UninstallDatabaseUser;
use App\Jobs\UpdateDatabaseUser;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\Server;
use App\Traits\HandlesUserContext;
use App\View\Components\StatusColumn;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

class CreateDatabaseUserWidget extends BaseWidget
{
    use HandlesUserContext;

    public Server $server;

    protected int|string|array $columnSpan = 'full';

    public function getListeners(): array
    {
        return [
            'echo-private:teams.'.auth()->user()->current_team_id.',DatabaseUserDeleted' => 'refreshComponent',
            'echo-private:teams.'.auth()->user()->current_team_id.',DatabaseUserUpdated' => 'refreshComponent',
        ];
    }

    public function refreshComponent(): void
    {
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(DatabaseUser::query()->where('server_id', $this->server->id))
            ->heading('Users')
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('status')
                    ->state(fn (DatabaseUser $record): string => StatusColumn::getStatus(record: $record))
                    ->alignEnd(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->model(DatabaseUser::class)
                    ->form([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique(modifyRuleUsing: fn (Unique $rule) => $rule->where('server_id', $this->server->id)),
                        TextInput::make('password')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255)
                            ->suffixAction(
                                Action::make('generate')
                                    ->icon('heroicon-s-sparkles')
                                    ->action(fn (Set $set, $state) => $set('password', Str::password()))
                            ),
                        CheckboxList::make('databases')
                            ->options(
                                $this->server->databases->pluck('name', 'id')
                                    ->toArray()
                            )
                            ->exists(
                                table: Database::class,
                                column: 'id',
                                modifyRuleUsing: fn (Exists $rule) => $rule->where('server_id', $this->server->id)
                            ),
                    ])
                    ->using(fn (array $data): DatabaseUser => DatabaseUser::create([
                        'name' => $data['name'],
                        'server_id' => $this->server->id,
                    ]))
                    ->after(function (DatabaseUser $record, array $data): void {
                        $this->logActivity(__("Created database user ':name' on server ':server'", ['name' => $record->name, 'server' => $this->server->name]), $record);

                        if (is_array($data['databases']) && ! empty($data['databases'])) {
                            $record->databases()->attach($data['databases']);
                        }

                        dispatch(new InstallDatabaseUser($record, $data['password'], auth()->user()));
                    })
                    ->successNotificationTitle(__('The database user will be created shortly.'))
                    ->createAnother(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->model(DatabaseUser::class)
                    ->form([
                        TextInput::make('name')
                            ->disabled(),
                        TextInput::make('password')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255)
                            ->helperText(__('Leave empty to keep the current password.'))
                            ->suffixAction(
                                Action::make('generate')
                                    ->icon('heroicon-s-sparkles')
                                    ->action(fn (Set $set, $state) => $set('password', Str::password()))
                            ),
                        CheckboxList::make('databases')
                            ->relationship(
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => $query->where('server_id', $this->server->id)
                            )
                            ->exists(
                                table: Database::class,
                                column: 'id',
                                modifyRuleUsing: fn (Exists $rule) => $rule->where('server_id', $this->server->id)
                            ),
                    ])
                    ->using(function (DatabaseUser $record, array $data): DatabaseUser {
                        $dataWithoutPassword = Arr::except($data, ['password']);
                        $record->update($dataWithoutPassword);

                        return $record;
                    })->after(function (DatabaseUser $record, array $data): void {
                        $record->forceFill([
                            'installed_at' => null,
                            'installation_failed_at' => null,
                            'uninstallation_failed_at' => null,
                        ])->save();

                        $this->logActivity(__("Updated database user ':name' on server ':server'", ['name' => $record->name, 'server' => $this->server->name]), $record);

                        dispatch(new UpdateDatabaseUser($record, $data['password'] ?? null, auth()->user()));
                    })
                    ->successNotificationTitle(__('The database user will be updated shortly.')),
                Tables\Actions\DeleteAction::make()
                    ->using(function (DatabaseUser $record): void {
                        $record->markUninstallationRequest();

                        dispatch(new UninstallDatabaseUser($record, $this->user()));

                        $this->logActivity(__("Deleted database user ':name' from server ':server'", ['name' => $record->name, 'server' => $record->server->name]), $record);

                        $this->sendNotification(__('The database user will be uninstalled from the server.'));
                    }),
            ])
            ->paginated(false);
    }
}
