<?php

namespace OwenIt\Auditing\Tests;

use Orchestra\Testbench\TestCase;
use OwenIt\Auditing\Models\Audit;

class AuditModelTest extends TestCase
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
