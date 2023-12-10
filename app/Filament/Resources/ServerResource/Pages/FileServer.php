<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\FileOnServer;
use App\Models\Server;
use App\Tasks\GetFile;
use App\Traits\BreadcrumbTrait;
use App\Traits\RedirectsIfProvisioned;
use Exception;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

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

    public string $fileContents = '';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        static::authorizeResourceAccess();
    }

    public function test(string $fileCrypted): void
    {
        $file = $this->findEditableFileByRouteParameter($this->getRecord(), $fileCrypted);

        try {
            $this->fileContents = $this->getRecord()->runTask(new GetFile($file->name))
                ->asRoot()
                ->throw()
                ->dispatch()
                ->getBuffer();

            $this->dispatch('open-modal', id: 'modaleEditFile');
        } catch (Exception) {
            Notification::make()
                ->title(__("Could not connect to the server ':server'", [
                    'server' => $this->getRecord()->name,
                ]))
                ->warning()
                ->send();
        }
    }

    private function findEditableFileByRouteParameter(Server $server, string $file): FileOnServer
    {
        $path = FileOnServer::pathFromRouteParameter($file);

        $file = $server->files()->editableFiles()->firstWhere('path', $path);

        abort_if($file === null, 404);

        return $file;
    }
}
