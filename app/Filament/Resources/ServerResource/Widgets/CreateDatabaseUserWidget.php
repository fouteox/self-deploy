<?php

namespace App\Filament\Resources\ServerResource\Widgets;

use App\Jobs\InstallDatabaseUser;
use App\Models\ActivityLog;
use App\Models\DatabaseUser;
use App\Models\Server;
use App\View\Components\StatusColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
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
                            ->required()
                            ->maxLength(255),
                    ])
                    ->using(fn (array $data, string $model): DatabaseUser => $model::create([
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

                        // TODO: add select database in form and attach it here

                        dispatch(new InstallDatabaseUser($record, $data['password'], auth()->user()));
                    })
                    ->successNotificationTitle(__('The database user will be created shortly.'))
                    ->createAnother(false),
            ])
            ->paginated(false);
    }
}