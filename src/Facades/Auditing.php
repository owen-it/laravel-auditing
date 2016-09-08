<?php

namespace OwenIt\Auditing\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see OwenIt\Auditing\Auditing
 */
class Auditing extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'OwenIt\Auditing\Auditing';
    }
}
