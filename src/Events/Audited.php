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

namespace OwenIt\Auditing\Events;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\AuditDriver;
use OwenIt\Auditing\Models\Audit;

class Audited
{
    /**
     * The Auditable model.
     *
     * @var \OwenIt\Auditing\Contracts\Auditable
     */
    public $model;

    /**
     * Audit driver.
     *
     * @var \OwenIt\Auditing\Contracts\AuditDriver
     */
    public $driver;

    /**
     * The report response.
     *
     * @var mixed
     */
    public $report;

    /**
     * Create a new Audited event instance.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable   $model
     * @param \OwenIt\Auditing\Contracts\AuditDriver $driver
     * @param \OwenIt\Auditing\Models\Audit          $audit
     */
    public function __construct(Auditable $model, AuditDriver $driver, Audit $audit = null)
    {
        $this->model = $model;
        $this->driver = $driver;
        $this->report = $audit;
    }
}
