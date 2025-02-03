<?php

namespace OwenIt\Auditing\Contracts;

/**
 * @deprecated
 * @see Resolver
 */
interface UrlResolver
{
    /**
     * Resolve the URL.
     */
    public static function resolve(): string;
}
