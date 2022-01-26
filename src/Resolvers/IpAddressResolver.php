<?php

namespace OwenIt\Auditing\Resolvers;

use Illuminate\Support\Facades\Request;
use OwenIt\Auditing\Contracts\Resolver;

class IpAddressResolver implements Resolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve($model): string
    {
        return Request::ip();
    }
}
