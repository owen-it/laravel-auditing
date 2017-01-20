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

    /**
     * Handle when a model is attached to a relation
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $relationParams
     *
     * @return void
     */
    public function attached($model, array $relationParams)
    {
        $model->prepareGeneralAuditData();
        $model->auditAttachedRelation($relationParams);
    }

    /**
     * Handle the when a model relation is updated
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $relationParams
     *
     * @return void
     */
    public function updatedRelation($model, array $relationParams)
    {
        $model->prepareGeneralAuditData();
        $model->auditUpdatedRelation($relationParams);
    }

    /**
     * Handle when a model is detached drom a relation
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $relationParams
     *
     * @return void
     */
    public function detached($model, array $relationParams)
    {
        $model->prepareGeneralAuditData();
        $model->auditDetachedRelation($relationParams);
    }
}
