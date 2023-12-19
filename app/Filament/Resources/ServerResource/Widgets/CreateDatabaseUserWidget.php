<?php

namespace App\Filament\Resources\ServerResource\Widgets;

use App\Jobs\InstallDatabaseUser;
use App\Jobs\UpdateDatabaseUser;
use App\Models\ActivityLog;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\Server;
use App\View\Components\StatusColumn;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

class CreateDatabaseUserWidget extends BaseWidget
{
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
                            ->maxLength(255),
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
                        ActivityLog::create([
                            'team_id' => auth()->user()->current_team_id,
                            'user_id' => auth()->user()->id,
                            'subject_id' => $record->getKey(),
                            'subject_type' => $record->getMorphClass(),
                            'description' => __("Created database user ':name' on server ':server'", ['name' => $record->name, 'server' => $this->server->name]),
                        ]);

                        if (is_array($data['databases']) && ! empty($data['databases'])) {
                            $record->databases()->attach($data['databases']);
                        }

                        dispatch(
                            new InstallDatabaseUser(
                                $record,
                                $data['password'],
                                auth()->user()
                            )
                        );
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
                            ->helperText(__('Leave empty to keep the current password.')),
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

                        ActivityLog::create([
                            'team_id' => auth()->user()->current_team_id,
                            'user_id' => auth()->user()->id,
                            'subject_id' => $record->getKey(),
                            'subject_type' => $record->getMorphClass(),
                            'description' => __("Updated database user ':name' on server ':server'", ['name' => $record->name, 'server' => $this->server->name]),
                        ]);

                        dispatch(new UpdateDatabaseUser($record, $data['password'] ?? null, auth()->user()));
                    })
                    ->successNotificationTitle(__('The database user will be updated shortly.')),
                Tables\Actions\DeleteAction::make(),
            ])
            ->paginated(false);
    }
}
