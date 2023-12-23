<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\FileOnServer;
use App\Models\File;
use App\Models\Server;
use App\Tasks\GetFile;
use App\Traits\BreadcrumbTrait;
use App\Traits\RedirectsIfProvisioned;
use Exception;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/* @method Server getRecord() */
class FileServer extends Page implements HasTable
{
    use BreadcrumbTrait, InteractsWithRecord, InteractsWithTable, RedirectsIfProvisioned {
        BreadcrumbTrait::getBreadcrumbs insteadof InteractsWithRecord;
    }

    protected static string $resource = ServerResource::class;

    protected static string $view = 'filament.resources.server-resource.pages.file-server';

    protected static ?string $title = 'Files';

    protected static ?string $navigationIcon = 'heroicon-s-document-text';

    public ?array $data = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->form->fill();

        static::authorizeResourceAccess();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(File::queryForFiles($this->getRecord()))
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
                            ->default(function (File $record) {

                                $file = $this->findEditableFileByRouteParameter($this->getRecord(), $record->id);

                                try {
                                    return $this->getRecord()->runTask(new GetFile($file->path))
                                        ->asRoot()
                                        ->throw()
                                        ->dispatch()
                                        ->getBuffer();
                                } catch (Exception) {
                                    // TODO: chercher une alternative en cas d'echec pour ne pas afficher la modal
                                    Notification::make()
                                        ->title(__("Could not connect to the server ':server'", [
                                            'server' => $this->getRecord()->name,
                                        ]))
                                        ->warning()
                                        ->send();

                                    return false;
                                }
                            })
                            ->autosize(),
                    ])
                    ->action(function (File $record) {
                        // TODO : ajouter la modification du fichier sur le serveur
                        dd($record);
                    }),
            ])
            ->paginated(false);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Textarea::make('fileContents')
                    ->autosize(),
            ])
            ->statePath('data');
    }

    private function findEditableFileByRouteParameter(Server $server, string $path): FileOnServer
    {
        $file = $server->files()->editableFiles()->firstWhere('path', $path);

        abort_if($file === null, 404);

        return $file;
    }

    public function openModal(string $encryptedFilePath): void
    {
        $this->dispatch('open-modal', id: 'modaleEditFile');
        $this->loadFile($encryptedFilePath);
    }

    public function loadFile(string $fileCrypted): void
    {
        $file = $this->findEditableFileByRouteParameter($this->getRecord(), $fileCrypted);

        try {
            $contents = $this->getRecord()->runTask(new GetFile($file->path))
                ->asRoot()
                ->throw()
                ->dispatch()
                ->getBuffer();

            $this->form->fill(['fileContents' => $contents]);
        } catch (Exception) {
            Notification::make()
                ->title(__("Could not connect to the server ':server'", [
                    'server' => $this->getRecord()->name,
                ]))
                ->warning()
                ->send();
            $this->dispatch('close-modal', id: 'modaleEditFile');
        }
    }

    public function create(): void
    {
        $file = $this->findEditableFileByRouteParameter($this->getRecord(), $file);

        dd($this->form->getState());
    }
}
