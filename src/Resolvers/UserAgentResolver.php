<?php

namespace OwenIt\Auditing\Resolvers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Request;

class UserAgentResolver implements \OwenIt\Auditing\Contracts\UserAgentResolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve()
    {
        return Str::limit(Request::header('User-Agent'), 255);
    }
}
