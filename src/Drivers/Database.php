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
    public function audit(Auditable $model): Audit
    {
        $implementation = Config::get('audit.implementation', \OwenIt\Auditing\Models\Audit::class);

        // If we want to avoid storing Audits with empty old_values & new_values, return null here.
        if (!Config::get('audit.empty_values')) {
            $attributeGetter = $model->resolveAttributeGetter($model->getAuditEvent());
            list($old, $new) = $model->$attributeGetter();
            if (empty($old) && empty($new)) {
                return null;
            }
        }

        return call_user_func([$implementation, 'create'], $model->toAudit());
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
                return $model->audits()
                    ->whereIn('id', $forRemoval)
                    ->delete() > 0;
            }
        }

        return false;
    }
}
