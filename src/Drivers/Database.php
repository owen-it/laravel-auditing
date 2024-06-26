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
            $auditKeyName = (new $class)->getKeyName();

            $forRemoval = $model->audits()
                ->latest()
                ->get()
                ->slice($threshold)
                ->pluck($auditKeyName);

            if (!$forRemoval->isEmpty()) {
                $forRemovalChunks = $forRemoval->chunk(Config::get('audit.placeholders_limit', 50000));
                $answer = false;
                foreach ($forRemovalChunks as $chunk) {
                    $answer = $model->audits()
                            ->whereIn($auditKeyName, $chunk)
                            ->delete() > 0 || $answer;
                }

                return $answer;
            }
        }

        return false;
    }
}
