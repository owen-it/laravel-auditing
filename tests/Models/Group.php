<?php

namespace OwenIt\Auditing\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Tests\database\factories\GroupFactory;

class Group extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;
    protected static string $factory = GroupFactory::class;
}
