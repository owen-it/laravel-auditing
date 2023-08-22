<?php

namespace OwenIt\Auditing;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Events\DispatchAudit;
use OwenIt\Auditing\Events\DispatchingAudit;

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
    public function retrieved(Auditable $model)
    {
        $this->dispatchAudit($model->setAuditEvent('retrieved'));
    }

    /**
     * Handle the created event.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function created(Auditable $model)
    {
        $this->dispatchAudit($model->setAuditEvent('created'));
    }

    /**
     * Handle the updated event.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function updated(Auditable $model)
    {
        // Ignore the updated event when restoring
        if (!static::$restoring) {
            $this->dispatchAudit($model->setAuditEvent('updated'));
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
        $this->dispatchAudit($model->setAuditEvent('deleted'));
    }

    /**
     * Handle the restoring event.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function restoring(Auditable $model)
    {
        // When restoring a model, an updated event is also fired.
        // By keeping track of the main event that took place,
        // we avoid creating a second audit with wrong values
        static::$restoring = true;
    }

    /**
     * Handle the restored event.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function restored(Auditable $model)
    {
        $this->dispatchAudit($model->setAuditEvent('restored'));

        // Once the model is restored, we need to put everything back
        // as before, in case a legitimate update event is fired
        static::$restoring = false;
    }

    protected function dispatchAudit(Auditable $model)
    {
        if (!$this->fireDispatchingAuditEvent($model)) {
            return;
        }

        DispatchAudit::dispatch($model);
    }

    /**
     * Fire the Auditing event.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return bool
     */
    protected function fireDispatchingAuditEvent(Auditable $model): bool
    {
        return app()->make('events')
                ->until(new DispatchingAudit($model)) !== false;
    }
}
