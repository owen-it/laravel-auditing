<?php

namespace OwenIt\Auditing\Tests;

use Mockery;
use OwenIt\Auditing\Contracts\Auditable;

class AuditableTest extends AbstractTestCase
{
    /**
     * Test the Auditable class instantiation.
     *
     * @return Auditable
     */
    public function testAuditableInstantiation()
    {
        $model = Mockery::mock(Auditable::class);

        $this->assertInstanceOf(Auditable::class, $model);

        return $model;
    }
}
