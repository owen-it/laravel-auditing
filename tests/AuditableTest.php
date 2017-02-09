<?php

namespace OwenIt\Auditing\Tests;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Mockery;
use OwenIt\Auditing\Tests\Stubs\AuditableModelStub;
use RuntimeException;

class AuditableTest extends AbstractTestCase
{
    /**
     * Test the Auditable toAudit() method to PASS (Empty data).
     *
     * @return void
     */
    public function testAuditableToAuditPassEmptyData()
    {
        $model = new AuditableModelStub();

        // Invalid auditable event
        $model->setAuditEvent('foo');

        $auditData = $model->toAudit();

        $this->assertEmpty($auditData);
    }

    /**
     * Test the Auditable toAudit() method to FAIL (Audit event method missing).
     *
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Unable to handle "foo" event, auditFooAttributes() method missing
     *
     * @return void
     */
    public function testAuditableToAuditFailAuditEventMethodMissing()
    {
        $model = Mockery::mock(AuditableModelStub::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $model->shouldReceive('isEventAuditable')
            ->andReturn(true);

        $model->setAuditEvent('foo');

        $model->toAudit();
    }

    /**
     * Test the Auditable toAudit() method to FAIL (Invalid User resolver).
     *
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Invalid User resolver type, callable expected
     *
     * @return void
     */
    public function testAuditableToAuditFailInvalidUserResolver()
    {
        Config::shouldReceive('get')
            ->once()
            ->with('audit.user.resolver')
            ->andReturn(null);

        $model = new AuditableModelStub();

        $model->setAuditEvent('created');

        $model->toAudit();

        Config::clearResolvedInstances();
    }

    /**
     * Test the Auditable toAudit() method to PASS
     *
     * @return void
     */
    public function testAuditableToAuditPass()
    {
        Config::shouldReceive('get')
            ->once()
            ->with('audit.user.resolver')
            ->andReturn(function () {
                return rand(1, 256);
            });

        Request::shouldReceive('ip')
            ->once()
            ->andReturn('127.0.0.1');

        $model = new AuditableModelStub();

        $model->setAuditEvent('created');

        $auditData = $model->toAudit();

        $this->assertArrayHasKey('old_values', $auditData);
        $this->assertArrayHasKey('new_values', $auditData);
        $this->assertArrayHasKey('event', $auditData);
        $this->assertArrayHasKey('auditable_id', $auditData);
        $this->assertArrayHasKey('auditable_type', $auditData);
        $this->assertArrayHasKey('user_id', $auditData);
        $this->assertArrayHasKey('url', $auditData);
        $this->assertArrayHasKey('ip_address', $auditData);
        $this->assertArrayHasKey('created_at', $auditData);
    }
}
