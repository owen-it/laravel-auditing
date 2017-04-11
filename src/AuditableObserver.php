<?php
/**
 * This file is part of the Laravel Auditing package.
 *
 * @author     Antério Vieira <anteriovieira@gmail.com>
 * @author     Quetzy Garcia  <quetzyg@altek.org>
 * @author     Raphael França <raphaelfrancabsb@gmail.com>
 * @copyright  2015-2017
 *
 * For the full copyright and license information,
 * please view the LICENSE.md file that was distributed
 * with this source code.
 */

namespace OwenIt\Auditing;

use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
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
    public function created(AuditableContract $model)
    {
        Auditor::execute($model->setAuditEvent('created'));
    }

    /**
     * Handle the updated event for the model.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function updated(AuditableContract $model)
    {
        if (
            $model->isDirty($model->getDeletedAtColumn()) &&
            !$model->deleted_at &&
            count($model->getDirty()) == 2 //deleted_at and updated_at
        ){
            // only restoring softdeleted item, no values changed other than deleted_at an updated_at
        }
        else {
            Auditor::execute($model->setAuditEvent('updated'));
        }
    }

    /**
     * Handle the deleted event for the model.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function deleted(AuditableContract $model)
    {
        Auditor::execute($model->setAuditEvent('deleted'));
    }

    /**
     * Handle the restored event for the model.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function restored(AuditableContract $model)
    {
        Auditor::execute($model->setAuditEvent('restored'));
    }
}
