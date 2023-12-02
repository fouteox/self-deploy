<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Models\Database;
use App\Traits\BreadcrumbTrait;
use App\Traits\RedirectsIfProvisioned;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DatabaseServer extends ManageRelatedRecords
{
    use BreadcrumbTrait, RedirectsIfProvisioned;

    protected static string $resource = ServerResource::class;

    protected static string $relationship = 'databases';

    protected static ?string $title = 'Databases';

    protected static ?string $navigationIcon = 'heroicon-s-circle-stack';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('server'))
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('users.name')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->placeholder('No user.'),
            ])
            ->filters([
                // ...
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('Add user')
                    ->icon('heroicon-s-clipboard-document')
                    ->form([
                        TextInput::make('name')
                            ->required(),
                    ])->action(function (Database $database, array $data) {

                        $database->users()->create([
                            'name' => $data['name'],
                            'server_id' => $database->server->id,
                        ]);

                        Notification::make()
                            ->title('User added')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // ...
            ])
            ->emptyStateActions([
                // ...
            ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required(),
            ]);
    }
}
