<?php

namespace App\SourceControl;

use App\Models\Credential;
use App\Provider;
use Exception;

class ProviderFactory
{
    public function forCredentials(Credential $credentials): mixed
    {
        return match ($credentials->provider) {
            Provider::Github => new Github($credentials->credentials['token']),

            default => throw new Exception('Invalid provider')
        };
    }
}
