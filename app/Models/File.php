<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class File extends Model
{
    use Sushi;

    protected static array $files = [];

    public $incrementing = false;

    protected $keyType = 'string';

    public static function queryForFiles(Server $server): Builder
    {
        static::$files = $server->files()->editableFiles()->toArray();

        return static::query();
    }

    public function getRows(): array
    {
        return collect(static::$files)->map(function ($file) {
            return [
                'id' => $file->path,
                'name' => $file->name,
                'description' => $file->description,
                'language' => $file->prismLanguage->value,
            ];
        })->toArray();
    }
}
