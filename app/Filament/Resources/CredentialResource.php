<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CredentialResource\Pages;
use App\Models\Credential;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CredentialResource extends Resource
{
    protected static ?string $model = Credential::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Placeholder::make('information')
                    ->hiddenLabel()
                    ->content(__('Connecting to Github will allow you to quickly select repositories and branches when deploying new sites.')),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('provider_name'),
                TextColumn::make('team')
                    ->default('-'),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->successNotificationTitle(__('Credentials deleted.')),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return Auth::user()->credentials()->getQuery();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCredentials::route('/'),
        ];
    }
}
