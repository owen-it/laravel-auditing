<?php

namespace OwenIt\Auditing\Observers;

use OwenIt\Auditing\Contracts\Auditable;

class Audit
{
    /**
     * Handle the saving event for the model.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function saving(Auditable $model)
    {
        $model->prepareAudit();
    }

    /**
     * Handle the created event for the model.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function created(Auditable $model)
    {
        $model->auditCreation();
    }

    /**
     * Handle the saved event for the model.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function saved(Auditable $model)
    {
        $model->auditUpdate();
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
        $model->prepareAudit();
        $model->auditDeletion();
    }
}
