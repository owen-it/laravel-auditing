<?php

namespace OwenIt\Auditing\Tests;

use Illuminate\Support\Facades\Config;
use Mockery;
use Orchestra\Testbench\TestCase;
use OwenIt\Auditing\Tests\Stubs\AuditableModelStub;
use RuntimeException;

class AuditableTest extends TestCase
{
    /**
     * Test the toAudit() method to FAIL (Invalid audit event).
     *
     * @expectedException        RuntimeException
     * @expectedExceptionMessage A valid audit event must be set
     *
     * @return void
     */
    public function testAuditableToAuditFailInvalidAuditEvent()
    {
        $model = new AuditableModelStub();

        // Invalid auditable event
        $model->setAuditEvent('foo');

        $model->toAudit();
    }

    /**
     * Test the toAudit() method to FAIL (Audit event method missing).
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
     * Test the toAudit() method to FAIL (Invalid User id resolver).
     *
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Invalid User resolver type, callable expected
     *
     * @return void
     */
    public function testAuditableToAuditFailInvalidUserIdResolver()
    {
        Config::set('audit.user.resolver', null);

        $model = new AuditableModelStub();

        $model->setAuditEvent('created');

        $model->toAudit();
    }

    /**
     * Test the toAudit() method to PASS.
     *
     * @return void
     */
    public function testAuditableToAuditPass()
    {
        Config::set('audit.user.resolver', function () {
            return rand(1, 256);
        });

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

    /**
     * Test the getAuditableEvents() method to PASS (default values).
     *
     * @return void
     */
    public function testAuditableGetAuditableEventsPassDefault()
    {
        $model = new AuditableModelStub();

        $events = $model->getAuditableEvents();

        $this->assertCount(4, $events);
    }

    /**
     * Test the getAuditableEvents() method to PASS (custom values).
     *
     * @return void
     */
    public function testAuditableGetAuditableEventsPassCustom()
    {
        $model = new AuditableModelStub();

        $model->auditableEvents = [
            'created',
        ];

        $events = $model->getAuditableEvents();

        $this->assertCount(1, $events);
    }
}
