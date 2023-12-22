<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class Software extends Model
{
    use Sushi;

    protected static array $softwares = [];

    public $incrementing = false;

    protected $keyType = 'string';

    public static function queryForSoftwares(Server $server): Builder
    {
        static::$softwares = $server->installedSoftware()->toArray();

        return static::query();
    }

    public function getRows(): array
    {
        return collect(static::$softwares)->map(function ($software) {
            return [
                'id' => $software->value,
                'name' => $software->getDisplayName(),
                'hasRestartTask' => (bool) $software->restartTaskClass(),
                'hasUpdateAlternativesTask' => (bool) $software->updateAlternativesTask(),
            ];
        })->toArray();
    }
}
