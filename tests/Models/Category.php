<?php

namespace OwenIt\Auditing\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Tests\Database\Factories\CategoryFactory;

class Category extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    public static function newFactory(): CategoryFactory
    {
        return new CategoryFactory();
    }
}
