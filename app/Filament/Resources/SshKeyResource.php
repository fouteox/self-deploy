<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SshKeyResource\Pages;
use App\Infrastructure\Entities\ServerStatus;
use App\Jobs\AddSshKeyToServer;
use App\Models\SshKey;
use App\Rules\PublicKey;
use Filament\Forms;
use Filament\Forms\Form;
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
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make(__('Add To Servers'))
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
                    }),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('public_key')
                    ->required()
                    ->rule(new PublicKey),
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
