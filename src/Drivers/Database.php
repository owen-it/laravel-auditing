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
            $forRemoval = $model->audits()
                ->latest()
                ->get()
                ->slice($threshold)
                ->pluck('id');

            if (!$forRemoval->isEmpty()) {
                $forRemovalChunks = $forRemoval->chunk(Config::get('audit.placeholders_limit', 50000));
                $answer = false;
                foreach ($forRemovalChunks as $chunk) {
                    $answer = $answer || $model->audits()
                            ->whereIn('id', $chunk)
                            ->delete() > 0;
                }

                return $answer;
            }
        }

        return false;
    }
}
