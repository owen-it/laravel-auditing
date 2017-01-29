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

namespace OwenIt\Auditing\Contracts;

interface Auditor
{
    /**
     * Get an audit driver instance.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return AuditDriver
     */
    public function auditDriver(Auditable $model);

    /**
     * Perform an audit.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function execute(Auditable $model);
}
