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

            return $model->audits()
                ->leftJoinSub(
                    $model->audits()->getQuery()
                        ->select($auditModel->getKeyName())->limit($threshold)->latest(),
                    'audit_threshold',
                    function ($join) use ($auditModel) {
                        $join->on(
                            $auditModel->gettable().'.'.$auditModel->getKeyName(),
                            '=',
                            'audit_threshold.'.$auditModel->getKeyName()
                        );
                    }
                )
                ->whereNull('audit_threshold.'.$auditModel->getKeyName())
                ->delete() > 0;
        }

        return false;
    }
}
