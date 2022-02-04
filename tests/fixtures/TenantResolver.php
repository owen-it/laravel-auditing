<?php

namespace OwenIt\Auditing\Tests\fixtures;

use OwenIt\Auditing\Contracts\Resolver;

class TenantResolver implements Resolver
{

    public static function resolve()
    {
        return 1;
    }
}