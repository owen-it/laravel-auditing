<?php

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
}
