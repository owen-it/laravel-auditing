<?php

namespace OwenIt\Auditing\Drivers;

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
            $auditClass = get_class($model->audits()->getModel());
            $auditModel = new $auditClass;
            $keyName = $auditModel->getKeyName();

            return $model->audits()
                ->leftJoinSub(
                    $model->audits()->getQuery()->select($keyName)->limit($threshold)->latest(),
                    'audit_threshold',
                    fn ($join) => $join->on(
                        $auditModel->getTable().".$keyName", '=', "audit_threshold.$keyName"
                    )
                )
                ->whereNull("audit_threshold.$keyName")
                ->delete() > 0;
        }

        return false;
    }
}
