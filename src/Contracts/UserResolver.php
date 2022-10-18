<?php

namespace OwenIt\Auditing\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface UserResolver
{
    /**
     * Resolve the User.
     *
     * @return Authenticatable|null
     */
    public static function resolve();
}
