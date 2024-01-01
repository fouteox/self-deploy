<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Models\PendingDeploymentException;
use App\Models\Server;
use App\Models\Site;
use App\Models\TlsSetting;
use App\Traits\HandlesUserContext;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

/* @method Site getRecord() */
class CreateSite extends CreateRecord
{
    use HandlesUserContext;

    protected static string $resource = SiteResource::class;

    protected static bool $canCreateAnother = false;

    /**
     * @throws Halt
     * @throws PendingDeploymentException
     */
    protected function handleRecordCreation(array $data): Model
    {
        $data['repository_url'] = $data['repository_provider'] ?? $data['repository_url'];

        $server = Server::find($data['server_id']);
        $site = $server->sites()->make(Arr::only($data, [
            'address',
            'php_version',
            'type',
            'web_folder',
            'zero_downtime_deployment',
            'repository_url',
            'repository_branch',
        ]));

        $site->tls_setting = TlsSetting::Auto;
        $site->user = $server->username;
        $site->path = "/home/$site->user/$site->address";
        $site->forceFill($site->type->defaultAttributes($site->zero_downtime_deployment));

        $userId = auth()->id();
        if (isset($data['deploy_key'])) {
            $deployKey = Cache::get("deploy-key-$userId-{$data['deploy_key_uuid']}");

            if (! $deployKey) {
                Notification::make()
                    ->title(__('The deploy key has expired. Please try again.'))
                    ->danger()
                    ->send();

                $this->halt();
            }

            $site->deploy_key_public = $deployKey->publicKey;
            $site->deploy_key_private = $deployKey->privateKey;
        }

        $site->save();

        $this->logActivity(__("Created site ':address' on server ':server'", ['address' => $site->address, 'server' => $server->name]), $site);

        $site->deploy(user: $this->user());

        Cache::forget("deploy-key-$userId-{$data['deploy_key_uuid']}");
        Cache::forget("deploy-key-uuid-$userId");

        return $site;
    }

    protected function getRedirectUrl(): string
    {
        return SiteResource::getUrl('deployments_site', ['record' => $this->getRecord()->id]);
    }
}
