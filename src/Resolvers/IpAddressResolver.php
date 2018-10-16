<?php

namespace OwenIt\Auditing\Resolvers;

use Illuminate\Support\Facades\Request;

class IpAddressResolver implements \OwenIt\Auditing\Contracts\IpAddressResolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve(): string
    {
        return Request::ip();
    }
}
