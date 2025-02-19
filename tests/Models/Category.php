<?php

namespace OwenIt\Auditing\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Tests\database\factories\CategoryFactory;

class Category extends \Illuminate\Database\Eloquent\Model {
    use HasFactory;

    protected static string $factory = CategoryFactory::class;
}
