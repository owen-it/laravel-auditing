<?php

namespace Tests\Stubs;

use Illuminate\Database\Eloquent\Model;

class EloquentModelStub extends Model
{
    public static $auditCustomFields = [
        'title'  => 'The title was defined as "{new.title||getNewTitle}"',
    ];

    public function getNewTitle($stub)
    {
        return 'new title';
    }
}
