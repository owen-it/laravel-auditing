<?php

namespace OwenIt\Auditing\Contracts;

interface Resolver
{
    /** @return string */
    public static function resolve(Auditable $auditable);
}
