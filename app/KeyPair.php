<?php

namespace App;

use Livewire\Wireable;

class KeyPair implements Wireable
{
    public function __construct(
        public readonly string $privateKey,
        public readonly string $publicKey,
        public readonly KeyPairType $type
    ) {
    }

    public static function fromLivewire($value): static
    {
        $privateKey = $value['privateKey'];
        $publicKey = $value['publicKey'];
        $type = $value['type'];

        return new static($privateKey, $publicKey, $type);
    }

    public function toLivewire(): array
    {
        return [
            'privateKey' => $this->privateKey,
            'publicKey' => $this->publicKey,
            'type' => $this->type,
        ];
    }
}
