<?php

namespace OwenIt\Auditing\Resolvers;

use Illuminate\Support\Facades\Request;
use OwenIt\Auditing\Contracts\Resolver;

class UserAgentResolver implements Resolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve($model)
    {
        return Request::header('User-Agent');
    }
}
