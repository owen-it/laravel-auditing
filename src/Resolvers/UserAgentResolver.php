<?php

namespace OwenIt\Auditing\Resolvers;

use Illuminate\Support\Facades\Request;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\Resolver;

class UserAgentResolver implements Resolver
{
    public static function resolve(Auditable $auditable): string
    {
        return $auditable->preloadedResolverData['user_agent'] ?? Request::header('User-Agent');
    }
}
