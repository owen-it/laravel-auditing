<?php

namespace OwenIt\Auditing\Tests\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Tests\database\factories\HasTestFactory;

class User extends Model implements Auditable, Authenticatable
{
    use \Illuminate\Auth\Authenticatable;
    use \OwenIt\Auditing\Auditable;
    use HasTestFactory;


    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'is_admin' => 'bool',
    ];

    /**
     * Uppercase first name character accessor.
     *
     * @param string $value
     *
     * @return string
     */
    public function getFirstNameAttribute(string $value): string
    {
        return ucfirst($value);
    }
}
