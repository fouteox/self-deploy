<?php

namespace App\Models;

use Filament\Support\Contracts\HasLabel;

enum TlsSetting: string implements HasLabel
{
    case Auto = 'auto';
    case Internal = 'internal';
    case Custom = 'custom';
    case Off = 'off';

    public function getDisplayName(): string
    {
        return $this->name;
    }

    public function getLabel(): ?string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Auto => __('Caddy automatically obtains and renews your site\'s TLS certificate using a public ACME CA such as Let\'s Encrypt.'),
            self::Internal => __('The TLS certificate for your site is generated internally, rather than relying on an external certificate authority. Useful for development environments.'),
            self::Custom => __('You provide your own TLS certificate and key for your site.'),
            self::Off => __('Turn off TLS for this site.'),
        };
    }
}
