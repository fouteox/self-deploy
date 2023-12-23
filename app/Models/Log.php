<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class Log extends Model
{
    use Sushi;

    protected static array $logs = [];

    public $incrementing = false;

    protected $keyType = 'string';

    public static function queryForLogs(Server $server): Builder
    {
        static::$logs = $server->logFiles();

        return static::query();
    }

    public function getRows(): array
    {
        return collect(static::$logs)->map(function ($log) {
            return [
                'id' => $log->path,
                'name' => $log->name,
                'description' => $log->description,
            ];
        })->toArray();
    }
}
