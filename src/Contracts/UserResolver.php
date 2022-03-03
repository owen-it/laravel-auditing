<?php

namespace OwenIt\Auditing\Contracts;

use Illuminate\Auth\Authenticatable;

interface UserResolver
{
    /**
     * Resolve the User.
     *
     * @return Authenticatable|null
     */
    public static function resolve();
}
