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

        if (empty(static::$softwares)) {
            static::$softwares = [[
                'id' => null,
                'name' => null,
                'hasRestartTask' => false,
                'hasUpdateAlternativesTask' => false,
            ]];
        }

        return static::query();
    }

    public function getRows(): array
    {
        return collect(static::$softwares)->map(function ($software) {
            return [
                'id' => is_array($software) ? $software['id'] : $software->value,
                'name' => is_array($software) ? $software['name'] : $software->getDisplayName(),
                'hasRestartTask' => (bool) (is_array($software) ? $software['hasRestartTask'] : $software->restartTaskClass()),
                'hasUpdateAlternativesTask' => (bool) (is_array($software) ? $software['hasUpdateAlternativesTask'] : $software->updateAlternativesTask()),
            ];
        })->toArray();
    }
}
