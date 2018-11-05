<?php

namespace OwenIt\Auditing\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \OwenIt\Auditing\Contracts\AuditDriver auditDriver(\OwenIt\Auditing\Contracts\Auditable $model);
 * @method static void execute(\OwenIt\Auditing\Contracts\Auditable $model);
 */
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
