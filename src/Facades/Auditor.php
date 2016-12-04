<?php

namespace OwenIt\Auditing\Facades;

use Illuminate\Support\Facades\Facade;

class Auditor extends Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor()
    {
        return \OwenIt\Auditing\Contracts\Auditor::class;
    }
}
