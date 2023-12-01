<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Models\Cron;
use App\Rules\CronExpression;
use App\Traits\BreadcrumbTrait;
use App\Traits\RedirectsIfProvisioned;
use Filament\Forms;
use Filament\Forms\Components\Radio;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CronServer extends ManageRelatedRecords
{
    use BreadcrumbTrait, RedirectsIfProvisioned;

    protected static string $resource = ServerResource::class;

    protected static string $relationship = 'crons';

    protected static ?string $title = 'Crons';

    protected static ?string $navigationIcon = 'heroicon-s-clock';

    public function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn (Builder $query) => $query->with('server'))
            ->columns([
                TextColumn::make('user'),
                TextColumn::make('expression')->description(function (string $state): string {
                    $options = Cron::predefinedFrequencyOptions();

                    return $options[$state] ?? __('Custom expression');
                }),
                TextColumn::make('command'),
            ])
            ->filters([
                // ...
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        return $this->mutateCronFormData($data);
                    })
                    ->successNotificationTitle(__('The Cron has been created and will be installed on the server.')),
                //                    ->visible(fn (): bool => $this->getRelationship()->getResults()->count()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        return $this->mutateCronFormData($data);
                    })
                    ->successNotificationTitle(__('The Cron has been updated.')),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    private function mutateCronFormData(array $data): array
    {
        $data['expression'] = $data['expression'] ?? $data['frequency'] ?? null;
        unset($data['frequency']);

        return $data;

        //            ->after(fn () => Notification::make()
        //                ->success()
        //                ->title(__('The Cron has been created and will be installed on the server.'))
        //                ->broadcast(auth()->user())
        //            );
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('command')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('php8.2 /home/eddy/site.com/current/artisan schedule:run'),
                Forms\Components\TextInput::make('user')
                    ->required()
                    ->maxLength(255)
                    ->in(['root', $this->getRecord()->username])
                    ->default($this->getRecord()->username),
                Forms\Components\Radio::make('frequency')
                    ->options(Cron::predefinedFrequencyOptions())
                    ->required()
                    ->in(array_keys(Cron::predefinedFrequencyOptions()))
                    ->live()
                    ->afterStateHydrated(function (Radio $component, ?Model $record): void {
                        if ($record) {
                            $component->state($record->expression && array_key_exists($record->expression, Cron::predefinedFrequencyOptions()) ? $record->expression : 'custom');
                        }
                    }),
                Forms\Components\TextInput::make('expression')
                    ->visible(fn (Get $get): bool => $get('frequency') === 'custom')
                    ->requiredIf('frequency', 'custom')
                    ->rule(new CronExpression)
                    ->maxLength(255),
            ]);
    }
}
