<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServerResource\Pages;
use App\Models\Server;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ServerResource extends Resource
{
    protected static ?string $model = Server::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('public_ipv4')
                    ->required()
                    ->ipv4(),
                Forms\Components\TextInput::make('ssh_port')
                    ->required()
                    ->rules(['integer', 'min:1', 'max:65535']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('public_ipv4')
                    ->label('IP Address'),
                Tables\Columns\TextColumn::make('status')
                    ->alignEnd(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(fn (Model $record) => $record->provisioned_at !== null),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->recordUrl(
                fn (Model $record): string => ! $record->provisioned_at ? ServerResource::getUrl('view', ['record' => $record]) : ServerResource::getUrl('edit', ['record' => $record])
            );
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Grid::make([
                    'default' => 1,
                    'md' => 3,
                ])
                    ->schema([
                        Section::make('Provision Command')
                            ->schema([
                                ViewEntry::make('provision_command')
                                    ->helperText('Cliquez sur ce bouton pour afficher une fenêtre modale avec la commande à exécuter sur votre serveur pour le provisionner.')
                                    ->view('filament.infolists.entries.provision-modal')
                                    ->hiddenLabel(),
                            ])
                            ->columnSpan(['default' => 1, 'md' => 1]),
                        Section::make([
                            TextEntry::make('name'),
                            TextEntry::make('public_ipv4')
                                ->label('IP Address'),
                        ])
                            ->columnSpan(['default' => 1, 'md' => 2]),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServers::route('/'),
            'create' => Pages\CreateServer::route('/create'),
            'edit' => Pages\EditServer::route('/{record}/edit'),
            'view' => Pages\ViewServer::route('/{record}'),
            'custom' => Pages\ServerProvisionning::route('/{record}/provision'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return Auth::user()->currentTeam->servers()->getQuery();
    }
}
