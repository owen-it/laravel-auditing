<?php

namespace OwenIt\Auditing\Contracts;

interface Resolver
{
    /**
     * @return string|null
     */
    public static function resolve(Auditable $auditable);
}
