<?php

namespace OwenIt\Auditing;

use Illuminate\Support\Facades\Config;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Events\DispatchAudit;
use OwenIt\Auditing\Events\DispatchingAudit;
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
     * @return void
     */
    public function retrieved(Auditable $model)
    {
        $this->dispatchAudit($model->setAuditEvent('retrieved'));
    }

    /**
     * Handle the created event.
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
     * @return void
     */
    public function updated(Auditable $model)
    {
        // Ignore the updated event when restoring
        if (! static::$restoring) {
            $this->dispatchAudit($model->setAuditEvent('updated'));
        }
    }

    /**
     * Handle the deleted event.
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
     * @return void
     */
    public function restored(Auditable $model)
    {
        $this->dispatchAudit($model->setAuditEvent('restored'));

        // Once the model is restored, we need to put everything back
        // as before, in case a legitimate update event is fired
        static::$restoring = false;
    }

    protected function dispatchAudit(Auditable $model): void
    {
        if (! $model->readyForAuditing()) {
            return;
        }

        // @phpstan-ignore method.notFound
        $model->preloadResolverData();
        if (! Config::get('audit.queue.enable', false)) {
            Auditor::execute($model);

            return;
        }

        if (! $this->fireDispatchingAuditEvent($model)) {
            return;
        }

        // Unload the relations to prevent large amounts of unnecessary data from being serialized.
        $model->withoutRelations();
        app()->make('events')->dispatch(new DispatchAudit($model));
    }

    /**
     * Fire the Auditing event.
     */
    protected function fireDispatchingAuditEvent(Auditable $model): bool
    {
        return app()->make('events')
            ->until(new DispatchingAudit($model)) !== false;
    }
}
