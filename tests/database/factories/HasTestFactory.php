<?php

namespace OwenIt\Auditing\Tests\database\factories;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

trait HasTestFactory
{
    use HasFactory;

    protected static function newFactory()
    {
        $modelName = Str::after(get_called_class(), 'Models\\');
        $path = 'OwenIt\Auditing\Tests\database\factories\\' . $modelName . 'Factory';

        return $path::new();
    }
}