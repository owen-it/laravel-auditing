<?php

namespace OwenIt\Auditing\Resolvers;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\Resolver;

class DumpResolver implements Resolver
{
    public static function resolve(Auditable $auditable): string
    {
        return '';
    }
}
