<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\KeyPairGenerator;
use App\Provider;
use App\Traits\HandlesUserContext;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateServer extends CreateRecord
{
    use HandlesUserContext;

    protected static string $resource = ServerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {

        //        $credentials = $customServer ? null : $this->user()->credentials()
        //            ->canBeUsedByTeam($this->team())
        //            ->findOrFail($request->validated('credentials_id'));

        $keyPairGenerator = new KeyPairGenerator;
        $keyPair = $keyPairGenerator->ed25519();
        $publicKey = $keyPair->publicKey;
        $private_key = $keyPair->privateKey;

        $working_directory = config('eddy.server_defaults.working_directory');
        $username = config('eddy.server_defaults.username');

        $password = Str::password(symbols: false);
        $database_password = Str::password(symbols: false);

        //        $public_ipv4 = $customServer ? $request->validated('public_ipv4') : null;
        //        $provider = $customServer ? Provider::CustomServer : $credentials->provider;

        $provider = Provider::CustomServer;

        //        $github_credentials_id = $request->boolean('add_key_to_github') ? $this->user()->githubCredentials?->id : null;

        return [
            ...$data,
            'team_id' => $this->team()->id,
            'created_by_user_id' => $this->user()->id,
            'provider' => $provider,
            'public_key' => $publicKey,
            'private_key' => $private_key,
            'working_directory' => $working_directory,
            'username' => $username,
            'password' => $password,
            'database_password' => $database_password,
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('provisioning', ['record' => $this->record]);
        //        return route('filament.admin.resources.servers.provisioning', ['record' => $this->record->id]);
    }
}
