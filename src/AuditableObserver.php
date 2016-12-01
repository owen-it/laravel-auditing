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
        $model->setEloquentEvent('created');

        Auditor::audit($model);
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
        $model->setEloquentEvent('updated');

        Auditor::audit($model);
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
        $model->setEloquentEvent('deleted');

        Auditor::audit($model);
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
        $model->setEloquentEvent('restored');

        Auditor::audit($model);
    }
}
