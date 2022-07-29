<?php

namespace OwenIt\Auditing\Tests\fixtures;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\Resolver;

class TenantResolver implements Resolver
{
    public static function resolve(Auditable $auditable)
    {
        return 1;
    }
}
