<?php

namespace App\Models;

use App\Server\Software as SoftwareEnum;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class Software extends Model
{
    use Sushi;

    protected static array $serverData = [];

    public static function initializeForServer(Server $server): void
    {
        self::$serverData = collect($server->installed_software)
            ->map(fn (string $softwareValue): array => self::mapSoftwareEnum($softwareValue))
            ->sortBy('name')
            ->toArray();
    }

    private static function mapSoftwareEnum(string $softwareValue): array
    {
        $softwareEnum = SoftwareEnum::from($softwareValue);

        return [
            'id' => $softwareEnum->value,
            'name' => $softwareEnum->getDisplayName(),
            'hasRestartTask' => (bool) $softwareEnum->restartTaskClass(),
            'hasUpdateAlternativesTask' => (bool) $softwareEnum->updateAlternativesTask(),
        ];
    }

    public function getRows(): array
    {
        return self::$serverData;
    }
}
