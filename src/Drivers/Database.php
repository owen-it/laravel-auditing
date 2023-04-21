<?php

namespace OwenIt\Auditing\Drivers;

use Illuminate\Support\Facades\Config;
use OwenIt\Auditing\Contracts\Audit;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\AuditDriver;

class Database implements AuditDriver
{
    /**
     * {@inheritdoc}
     */
    public function audit(Auditable $model): ?Audit
    {
        $implementation = Config::get('audit.implementation', \OwenIt\Auditing\Models\Audit::class);
        $callback = [$implementation, 'create'];

        if (! is_callable($callback)) {
            throw new \UnexpectedValueException("Method config('audit.implementation')::create() does not exist.");
        }

        return call_user_func($callback, $model->toAudit());
    }

    /**
     * {@inheritdoc}
     */
    public function prune(Auditable $model): bool
    {
        if (($threshold = $model->getAuditThreshold()) > 0) {
            return $model->audits()
                ->latest()
                ->offset($threshold)->limit(PHP_INT_MAX)
                ->delete() > 0;
        }

        return false;
    }
}
