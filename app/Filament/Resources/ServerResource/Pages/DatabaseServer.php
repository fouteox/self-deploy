<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Filament\Resources\ServerResource\Widgets\CreateDatabaseUserWidget;
use App\Jobs\InstallDatabase;
use App\Jobs\InstallDatabaseUser;
use App\Models\ActivityLog;
use App\Models\Database;
use App\Models\Server;
use App\Traits\BreadcrumbTrait;
use App\Traits\RedirectsIfProvisioned;
use App\View\Components\StatusColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Bus;
use Illuminate\Validation\Rules\Unique;

/* @method Server getRecord() */
class DatabaseServer extends ManageRelatedRecords
{
    use BreadcrumbTrait, RedirectsIfProvisioned;

    protected static string $resource = ServerResource::class;

    protected static string $relationship = 'databases';

    protected static ?string $title = 'Databases';

    protected static ?string $navigationIcon = 'heroicon-s-circle-stack';

    public function getListeners(): array
    {
        return [
            'echo-private:teams.'.auth()->user()->current_team_id.',DatabaseDeleted' => 'refreshComponent',
            'echo-private:teams.'.auth()->user()->current_team_id.',DatabaseUpdated' => 'refreshComponent',
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
            ->modifyQueryUsing(fn ($query) => $query->with('server'))
            ->heading('Databases')
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('users.name')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->placeholder('No user.'),
                TextColumn::make('status')
                    ->state(fn (Database $record): string => StatusColumn::getStatus(record: $record))
                    ->alignEnd(),
            ])
            ->filters([
                // ...
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->using(fn (array $data): Database => Database::create([
                        'name' => $data['name'],
                        'server_id' => $this->getRecord()->id,
                    ]))
                    ->after(function (Database $record, array $data): void {
                        ActivityLog::create([
                            'team_id' => auth()->user()->current_team_id,
                            'user_id' => auth()->user()->id,
                            'subject_id' => $record->getKey(),
                            'subject_type' => $record->getMorphClass(),
                            'description' => __("Created database ':name' on server ':server'", ['name' => $record->name, 'server' => $this->getRecord()->name]),
                        ]);

                        if (! $data['create_user']) {
                            dispatch(new InstallDatabase($record, auth()->user()));

                            Notification::make()
                                ->title(__('The database will be created shortly.'))
                                ->success()
                                ->send();
                        } else {
                            $databaseUser = $record->users()->make([
                                'name' => $data['user'],
                            ])->forceFill([
                                'server_id' => $this->getRecord()->id,
                            ]);

                            $databaseUser->save();
                            $databaseUser->databases()->attach($record);

                            ActivityLog::create([
                                'team_id' => auth()->user()->current_team_id,
                                'user_id' => auth()->user()->id,
                                'subject_id' => $record->getKey(),
                                'subject_type' => $record->getMorphClass(),
                                'description' => __("Created database user ':name' on server ':server'", ['name' => $databaseUser->name, 'server' => $this->getRecord()->name]),
                            ]);

                            Bus::chain([
                                new InstallDatabase($record, auth()->user()),
                                new InstallDatabaseUser($databaseUser, $data['password'], auth()->user()),
                            ])->dispatch();

                            Notification::make()
                                ->title(__('The database and user will be created shortly.'))
                                ->success()
                                ->send();
                        }
                    })
                    ->createAnother(false)
                    ->successNotificationTitle(false),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // ...
            ])
            ->emptyStateActions([
                // ...
            ])
            ->paginated(false);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(modifyRuleUsing: fn (Unique $rule) => $rule->where('server_id', $this->getRecord()->id))
                    ->columnSpanFull(),
                Toggle::make('create_user')
                    ->label(__('Create user for new database'))
                    ->columnSpanFull()
                    ->required()
                    ->live(),
                TextInput::make('user')
                    ->label(__('User'))
                    ->columnSpanFull()
                    ->maxLength(255)
                    ->unique(
                        table: 'database_users',
                        column: 'name',
                        modifyRuleUsing: fn (Unique $rule) => $rule->where('server_id', $this->getRecord()->id)
                    )
                    ->hidden(fn (Get $get): bool => ! $get('create_user'))
                    ->required(fn (Get $get): bool => filled($get('create_user'))),
                TextInput::make('password')
                    ->label(__('Password'))
                    ->columnSpanFull()
                    ->maxLength(255)
                    ->hidden(fn (Get $get): bool => ! $get('create_user'))
                    ->required(fn (Get $get): bool => filled($get('create_user'))),
            ]);
    }

    protected function getFooterWidgets(): array
    {
        return [
            CreateDatabaseUserWidget::make([
                'server' => $this->getRecord(),
            ]),
        ];
    }
}
