<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Models\Log;
use App\Models\Server;
use App\Tasks\GetFile;
use App\Traits\BreadcrumbTrait;
use App\Traits\RedirectsIfProvisioned;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/* @method Server getRecord() */
class LogServer extends Page implements HasTable
{
    use BreadcrumbTrait, InteractsWithRecord, InteractsWithTable, RedirectsIfProvisioned {
        BreadcrumbTrait::getBreadcrumbs insteadof InteractsWithRecord;
    }

    protected static string $resource = ServerResource::class;

    protected static string $view = 'filament.resources.server-resource.pages.log-server';

    protected static ?string $title = 'Logs';

    protected static ?string $navigationIcon = 'heroicon-s-book-open';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        static::authorizeResourceAccess();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Log::queryForLogs($this->getRecord()))
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('description')
                    ->wrap(),
            ])
            ->actions([
                Action::make('view')
                    ->button()
                    ->form([
                        Textarea::make('fileContents')
                            ->default(function (Log $record) {
                                // TODO : ajouter la vérification en cas de fail de chargement du fichier
                                return $this->getRecord()->runTask(new GetFile($record->id))
                                    ->asRoot()
                                    ->throw()
                                    ->dispatch()
                                    ->getBuffer();
                            })
                            ->autosize(),
                    ]),
            ])
            ->paginated(false);
    }
}
