<?php

namespace OwenIt\Auditing;

class AuditObserver
{
    /**
     * Handle the saving event for the model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return void
     */
    public function saving($model)
    {
        $model->prepareAudit();
    }

    /**
     * Handle the created event for the model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return void
     */
    public function created($model)
    {
        $model->auditCreation();
    }

    /**
     * Handle the saved event for the model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return void
     */
    public function saved($model)
    {
        $model->auditUpdate();
    }

    /**
     * Handle the deleted event for the model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return void
     */
    public function deleted($model)
    {
        $model->prepareAudit();
        $model->auditDeletion();
    }
}
