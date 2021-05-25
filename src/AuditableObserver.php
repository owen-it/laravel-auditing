<?php

namespace OwenIt\Auditing;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Facades\Auditor;

class AuditableObserver
{
    /**
     * Is the model being restored?
     *
     * @var bool
     */
    public static $restoring = false;

    /**
     * Handle the retrieved event.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function retrieved($model)
    {
        if ($model instanceof Auditable) {
            Auditor::execute($model->setAuditEvent('retrieved'));
        }
    }

    /**
     * Handle the created event.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function created($model)
    {
        if ($model instanceof Auditable) {
            Auditor::execute($model->setAuditEvent('created'));
        }
    }

    /**
     * Handle the updated event.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function updated($model)
    {
        if ($model instanceof Auditable) {
            // Ignore the updated event when restoring
            if (!static::$restoring) {
                Auditor::execute($model->setAuditEvent('updated'));
            }
        }
    }

    /**
     * Handle the deleted event.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function deleted(Auditable $model)
    {
        if ($model instanceof Auditable) {
            Auditor::execute($model->setAuditEvent('deleted'));
        }
    }

    /**
     * Handle the restoring event.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function restoring($model)
    {
        if ($model instanceof Auditable) {
            // When restoring a model, an updated event is also fired.
            // By keeping track of the main event that took place,
            // we avoid creating a second audit with wrong values
            static::$restoring = true;
        }
    }

    /**
     * Handle the restored event.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function restored($model)
    {
        if ($model instanceof Auditable) {
            Auditor::execute($model->setAuditEvent('restored'));
            // Once the model is restored, we need to put everything back
            // as before, in case a legitimate update event is fired
            static::$restoring = false;
        }
    }
}
