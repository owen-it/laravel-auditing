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

namespace OwenIt\Auditing\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class AuditableModelStub extends Model implements AuditableContract
{
    use Auditable;

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'published' => 'bool',
    ];

    /**
     * {@inheritdoc}
     */
    public function resolveIpAddress()
    {
        return '127.0.0.1';
    }

    /**
     * Set the value of the Audit driver.
     *
     * @param string $driver
     *
     * @return void
     */
    public function setAuditDriver($driver)
    {
        $this->auditDriver = $driver;
    }

    /**
     * Set the value of the Audit threshold.
     *
     * @param int $threshold
     *
     * @return void
     */
    public function setAuditThreshold($threshold)
    {
        $this->auditThreshold = $threshold;
    }

    /**
     * Uppercase Title accessor.
     *
     * @param string $value
     *
     * @return string
     */
    public function getTitleAttribute($value)
    {
        return strtoupper($value);
    }
}
