<?php

namespace OwenIt\Auditing\Contracts;

interface Resolver
{
    /** @return mixed */
    public static function resolve(Auditable $auditable);
}
