<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\FileOnServer;
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
use Livewire\Attributes\Js;

/* @method Server getRecord() */
class FileServer extends Page
{
    use BreadcrumbTrait, InteractsWithRecord, RedirectsIfProvisioned {
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

    private function findEditableFileByRouteParameter(Server $server, string $file): FileOnServer
    {
        $path = FileOnServer::pathFromRouteParameter($file);

        $file = $server->files()->editableFiles()->firstWhere('path', $path);

        abort_if($file === null, 404);

        return $file;
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

    #[Js]
    public function resetData()
    {
        return <<<'JS'
                    $wire.data = [];
                    $dispatch('close-modal', { id: 'modaleEditFile' });
                JS;
    }

    public function create(): void
    {
        $file = $this->findEditableFileByRouteParameter($this->getRecord(), $file);

        dd($this->form->getState());
    }
}
