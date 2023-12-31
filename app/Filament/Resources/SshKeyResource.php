<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SshKeyResource\Pages;
use App\Infrastructure\Entities\ServerStatus;
use App\Jobs\AddSshKeyToServer;
use App\Jobs\RemoveSshKeyFromServer;
use App\Models\Server;
use App\Models\SshKey;
use App\Rules\PublicKey;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Exists;

class SshKeyResource extends Resource
{
    protected static ?string $model = SshKey::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('fingerprint'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make(__('Add To Servers'))
                        ->modalHeading(__('Add SSH Key to Servers'))
                        ->form([
                            Forms\Components\Select::make('servers')
                                ->label(__('Select servers to add SSH Key to.'))
                                ->options(
                                    Auth::user()->currentTeam->servers()
                                        ->where('status', '!=', ServerStatus::Deleting)
                                        ->get()
                                        ->mapWithKeys(fn ($server) => [$server->id => $server->name_with_ip])
                                )
                                ->multiple()
                                ->required()
                                ->exists(
                                    table: 'servers',
                                    column: 'id',
                                    modifyRuleUsing: function (Exists $rule) {
                                        return $rule->where('team_id', Auth::user()->currentTeam->id);
                                    }
                                ),
                        ])
                        ->action(function (array $data, SshKey $record) {
                            collect($data['servers'])->each(function ($serverId) use ($record) {
                                $server = Auth::user()->currentTeam->servers()->findOrFail($serverId);

                                dispatch(new AddSshKeyToServer($record, $server));
                            });

                            Notification::make()
                                ->title(__('The SSH Key will be added to the selected servers. This may take a few minutes.'))
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make(__('Remove From Servers'))
                        ->modalHeading(__('Remove SSH Key from Servers'))
                        ->form([
                            Forms\Components\Select::make('servers')
                                ->label(__('Select servers to remove SSH Key from.'))
                                ->options(
                                    Auth::user()->currentTeam->servers()
                                        ->where('status', '!=', ServerStatus::Deleting)
                                        ->get()
                                        ->mapWithKeys(fn ($server) => [$server->id => $server->name_with_ip])
                                )
                                ->multiple()
                                ->required()
                                ->exists(
                                    table: 'servers',
                                    column: 'id',
                                    modifyRuleUsing: function (Exists $rule) {
                                        return $rule->where('team_id', Auth::user()->currentTeam->id);
                                    }
                                ),
                        ])
                        ->action(function (array $data, SshKey $record) {
                            collect($data['servers'])->each(function ($serverId) use ($record) {
                                $server = Auth::user()->currentTeam->servers()->findOrFail($serverId);

                                dispatch(new RemoveSshKeyFromServer($record->public_key, $server));
                            });

                            Notification::make()
                                ->title(__('The SSH Key will be removed from the selected servers. This may take a few minutes.'))
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteAction::make('delete')
                        ->label(__('Delete Key'))
                        ->successNotificationTitle(__('SSH Key deleted.')),
                    Tables\Actions\DeleteAction::make('delete-and-remove-from-servers')
                        ->label(__('Delete Key and Remove From Servers'))
                        ->action(function (SshKey $record) {

                            Auth::user()->currentTeam->servers()->each(function (Server $server) use ($record) {
                                dispatch(new RemoveSshKeyFromServer($record->public_key, $server));
                            });

                            $record->delete();

                            Notification::make()
                                ->title(__('The SSH Key will be deleted and removed from all servers. This may take a few minutes.'))
                                ->success()
                                ->send();
                        }),
                ])
                    ->button()
                    ->label(__('Actions')),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('public_key')
                    ->required()
                    ->rule(new PublicKey)
                    ->columnSpanFull(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return Auth::user()->sshKeys()->getQuery();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSshKeys::route('/'),
        ];
    }
}
