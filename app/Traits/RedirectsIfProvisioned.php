<?php

namespace App\Traits;

use App\Filament\Resources\ServerResource;
use App\Models\Server;

trait RedirectsIfProvisioned
{
    public function bootRedirectsIfProvisioned(): void
    {
        $record = $this->resolveRecordInstance($this->record);

        if ($record->provisioned_at === null) {
            $this->redirect(ServerResource::getUrl('provisioning', ['record' => $record]));
        }
    }

    protected function resolveRecordInstance($record): array|Server
    {
        if ($record instanceof Server) {
            return $record;
        }

        return Server::findOrFail($record);
    }
}
