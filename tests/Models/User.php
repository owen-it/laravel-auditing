<?php

namespace OwenIt\Auditing\Tests\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Tests\database\factories\HasTestFactory;

class User extends Model implements Auditable, Authenticatable
{
    use HasTestFactory;
    use \Illuminate\Auth\Authenticatable;
    use \OwenIt\Auditing\Auditable;

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'is_admin' => 'bool',
    ];

    /**
     * Uppercase first name character accessor.
     */
    public function getFirstNameAttribute(string $value): string
    {
        return ucfirst($value);
    }
}
