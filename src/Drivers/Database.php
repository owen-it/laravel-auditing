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

namespace OwenIt\Auditing\Drivers;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\AuditDriver;
use OwenIt\Auditing\Models\Audit;

class Database implements AuditDriver
{
    /**
     * {@inheritdoc}
     */
    public function audit(Auditable $model)
    {
        return Audit::create($model->toAudit());
    }

    /**
     * {@inheritdoc}
     */
    public function prune(Auditable $model)
    {
        if (($threshold = $model->getAuditThreshold()) > 0) {
            $total = $model->audits()->count();

            $forRemoval = ($total - $threshold);

            if ($forRemoval > 0) {
                $model->audits()
                    ->orderBy('created_at', 'asc')
                    ->limit($forRemoval)
                    ->delete();

                return true;
            }
        }

        return false;
    }
}
