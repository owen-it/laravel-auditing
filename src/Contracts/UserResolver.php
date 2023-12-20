<?php

namespace OwenIt\Auditing\Contracts;

interface UserResolver
{
    /**
     * Resolve the User.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public static function resolve();
}
