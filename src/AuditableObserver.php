<?php
/**
 * This file is part of the Laravel Auditing package.
 *
 * @author     Antério Vieira <anteriovieira@gmail.com>
 * @author     Quetzy Garcia  <quetzyg@altek.org>
 * @author     Raphael França <raphaelfrancabsb@gmail.com>
 *
 * @copyright  2015-2017
 *
 * For the full copyright and license information,
 * please view the LICENSE.md file that was distributed
 * with this source code.
 */

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
        Auditor::execute($model->setAuditEvent('created'));
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
        Auditor::execute($model->setAuditEvent('updated'));
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
        Auditor::execute($model->setAuditEvent('deleted'));
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
        Auditor::execute($model->setAuditEvent('restored'));
    }
}
