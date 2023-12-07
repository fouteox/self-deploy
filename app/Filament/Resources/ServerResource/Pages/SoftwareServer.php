<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Jobs\MakeSoftwareDefaultOnServer;
use App\Jobs\RestartSoftwareOnServer;
use App\Models\ActivityLog;
use App\Models\Server;
use App\Server\Software;
use App\Traits\BreadcrumbTrait;
use App\Traits\RedirectsIfProvisioned;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

/* @method Server getRecord() */
class SoftwareServer extends Page implements HasActions
{
    use BreadcrumbTrait, InteractsWithRecord, RedirectsIfProvisioned {
        BreadcrumbTrait::getBreadcrumbs insteadof InteractsWithRecord;
    }

    protected static string $resource = ServerResource::class;

    protected static string $view = 'filament.resources.server-resource.pages.software-server';

    protected static ?string $title = 'Softwares';

    protected static ?string $navigationIcon = 'heroicon-s-code-bracket';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        static::authorizeResourceAccess();
    }

    public function restart(string $softwareId): void
    {
        $software = Software::from($softwareId);

        dispatch(new RestartSoftwareOnServer($this->getRecord(), $software));

        ActivityLog::create([
            'team_id' => auth()->user()->current_team_id,
            'user_id' => auth()->user()->id,
            'subject_id' => $this->getRecord()->getKey(),
            'subject_type' => $this->getRecord()->getMorphClass(),
            'description' => __("Restarted ':software' on server ':server'", ['software' => $software->getDisplayName(), 'server' => $this->getRecord()->name]),
        ]);

        Notification::make()
            ->title(__(':software will be restarted on the server.', ['software' => $software->getDisplayName()]))
            ->success()
            ->send();

        $this->dispatch('close-modal', id: $softwareId);
    }

    public function default(string $softwareId): void
    {
        $software = Software::from($softwareId);

        dispatch(new MakeSoftwareDefaultOnServer($this->getRecord(), $software));

        ActivityLog::create([
            'team_id' => auth()->user()->current_team_id,
            'user_id' => auth()->user()->id,
            'subject_id' => $this->getRecord()->getKey(),
            'subject_type' => $this->getRecord()->getMorphClass(),
            'description' => __("Made ':software' the CLI default on server ':server'", ['software' => $software->getDisplayName(), 'server' => $this->getRecord()->name]),
        ]);

        Notification::make()
            ->title(__(':software will now be the CLI default on the server.', ['software' => $software->getDisplayName()]))
            ->success()
            ->send();

        $this->dispatch('close-modal', id: $softwareId);
    }
}
