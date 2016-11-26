<?php

namespace OwenIt\Auditing\Auditors;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Models\Audit;

class DatabaseAuditor
{
    /**
     * Perform an audit to the Auditable model.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function audit(Auditable $model)
    {
        $report = Audit::create($model->toAudit());

        if ($report) {
            $model->clearOlderAudits();
        }

        return $report;
    }
}
