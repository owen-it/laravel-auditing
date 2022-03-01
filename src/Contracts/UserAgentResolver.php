<?php

namespace OwenIt\Auditing\Contracts;

/**
 * @deprecated
 * @see Resolver
 */
interface UserAgentResolver
{
    /**
     * Resolve the User Agent.
     *
     * @return string|null
     */
    public static function resolve();
}
