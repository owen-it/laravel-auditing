<?php

namespace OwenIt\Auditing\Resolvers;

use Illuminate\Support\Facades\Request;

class UserAgentResolver implements \OwenIt\Auditing\Contracts\UserAgentResolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve()
    {
        return Request::header('User-Agent');
    }
}
