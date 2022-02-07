<?php

namespace OwenIt\Auditing\Contracts;

interface Resolver
{
    public static function resolve(Auditable $auditable);
}
