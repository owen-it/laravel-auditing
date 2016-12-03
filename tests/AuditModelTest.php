<?php

namespace OwenIt\Auditing\Tests;

use OwenIt\Auditing\Models\Audit;

class AuditModelTest extends AbstractTestCase
{
    /**
     * Test the Audit class instantiation.
     *
     * @return Audit
     */
    public function testAuditInstantiation()
    {
        $audit = new Audit();

        $this->assertInstanceOf(Audit::class, $audit);

        return $audit;
    }
}
