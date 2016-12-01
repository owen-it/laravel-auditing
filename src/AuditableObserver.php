<?php

namespace OwenIt\Auditing;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Facades\Auditor;

class AuditableObserver
{
    /**
     * Handle the created event for the model.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function created(Auditable $model)
    {
        Auditor::audit($model->setAuditEvent('created'));
    }

    /**
     * Handle the updated event for the model.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function updated(Auditable $model)
    {
        Auditor::audit($model->setAuditEvent('updated'));
    }

    /**
     * Handle the deleted event for the model.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function deleted(Auditable $model)
    {
        Auditor::audit($model->setAuditEvent('deleted'));
    }

    /**
     * Handle the restored event for the model.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function restored(Auditable $model)
    {
        Auditor::audit($model->setAuditEvent('restored'));
    }
}
