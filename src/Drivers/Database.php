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
        return call_user_func([get_class($model->audits()->getModel()), 'create'], $model->toAudit());
    }

    /**
     * {@inheritdoc}
     */
    public function prune(Auditable $model): bool
    {
        if (($threshold = $model->getAuditThreshold()) > 0) {
            $class = get_class($model->audits()->getModel());
            $keyName = (new $class)->getKeyName();

            return $model->audits()
                ->whereNotIn(
                    $keyName, $model->audits()->select($keyName)->limit($threshold)->latest()
                )
                ->delete() > 0;
        }

        return false;
    }
}
