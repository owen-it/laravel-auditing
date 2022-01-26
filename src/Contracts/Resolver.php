<?php

namespace OwenIt\Auditing\Contracts;

use Illuminate\Database\Eloquent\Model;

interface Resolver
{
    /**
     * @param Model|\OwenIt\Auditing\Auditable $model
     * @return mixed
     */
    public static function resolve(Model $model);
}
