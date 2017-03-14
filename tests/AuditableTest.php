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

namespace OwenIt\Auditing\Tests;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Mockery;
use Orchestra\Testbench\TestCase;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Tests\Stubs\AuditableModelStub;
use RuntimeException;

class AuditableTest extends TestCase
{
    /**
     * Set test attributes to an Auditable instance.
     *
     * @param Auditable $audit
     *
     * @return void
     */
    private function setAuditableTestAttributes(Auditable $audit)
    {
        $audit->created_at = Carbon::now();
        $audit->updated_at = Carbon::now();
        $audit->title      = 'How To Audit Eloquent Models';
        $audit->content    = 'First step: install the laravel-auditing package.';
        $audit->published  = 1;
    }

    /**
     * Test the toAudit() method to FAIL (Invalid audit event).
     *
     * @expectedException        RuntimeException
     * @expectedExceptionMessage A valid audit event must be set
     *
     * @return void
     */
    public function testToAuditFailInvalidAuditEvent()
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
    public function testToAuditFailAuditEventMethodMissing()
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
    public function testToAuditFailInvalidUserIdResolver()
    {
        Config::set('audit.user.resolver', null);

        $model = new AuditableModelStub();

        $model->setAuditEvent('created');

        $model->toAudit();
    }

    /**
     * Test the toAudit() method to PASS (default).
     *
     * @return void
     */
    public function testToAuditPassDefault()
    {
        Config::set('audit.user.resolver', function () {
            return rand(1, 256);
        });

        $model = new AuditableModelStub();
        $this->setAuditableTestAttributes($model);

        $model->setAuditEvent('created');
        $auditData = $model->toAudit();

        // Audit attributes
        $this->assertCount(9, $auditData);

        $this->assertArrayHasKey('old_values', $auditData);
        $this->assertArrayHasKey('new_values', $auditData);
        $this->assertArrayHasKey('event', $auditData);
        $this->assertArrayHasKey('auditable_id', $auditData);
        $this->assertArrayHasKey('auditable_type', $auditData);
        $this->assertArrayHasKey('user_id', $auditData);
        $this->assertArrayHasKey('url', $auditData);
        $this->assertArrayHasKey('ip_address', $auditData);
        $this->assertArrayHasKey('created_at', $auditData);

        // Modified Auditable attributes
        $this->assertCount(3, $auditData['new_values']);

        $this->assertArrayHasKey('title', $auditData['new_values']);
        $this->assertArrayHasKey('content', $auditData['new_values']);
        $this->assertArrayHasKey('published', $auditData['new_values']);
    }

    /**
     * Test the toAudit() method to PASS (included attributes).
     *
     * @return void
     */
    public function testToAuditPassIncludedAttributes()
    {
        Config::set('audit.user.resolver', function () {
            return rand(1, 256);
        });

        $model = new AuditableModelStub();
        $this->setAuditableTestAttributes($model);

        // Set included attributes
        $model->setAuditInclude([
            'title',
            'content',
        ]);

        $model->setAuditEvent('created');
        $auditData = $model->toAudit();

        // Audit attributes
        $this->assertCount(9, $auditData);

        $this->assertArrayHasKey('old_values', $auditData);
        $this->assertArrayHasKey('new_values', $auditData);
        $this->assertArrayHasKey('event', $auditData);
        $this->assertArrayHasKey('auditable_id', $auditData);
        $this->assertArrayHasKey('auditable_type', $auditData);
        $this->assertArrayHasKey('user_id', $auditData);
        $this->assertArrayHasKey('url', $auditData);
        $this->assertArrayHasKey('ip_address', $auditData);
        $this->assertArrayHasKey('created_at', $auditData);

        // Modified Auditable attributes
        $this->assertCount(2, $auditData['new_values']);

        $this->assertArrayHasKey('title', $auditData['new_values']);
        $this->assertArrayHasKey('content', $auditData['new_values']);
    }

    /**
     * Test the toAudit() method to PASS (excluded attributes).
     *
     * @return void
     */
    public function testToAuditPassExcludedAttributes()
    {
        Config::set('audit.user.resolver', function () {
            return rand(1, 256);
        });

        $model = new AuditableModelStub();
        $this->setAuditableTestAttributes($model);

        // Set excluded attributes
        $model->setAuditExclude([
            'content',
        ]);

        $model->setAuditEvent('created');
        $auditData = $model->toAudit();

        // Audit attributes
        $this->assertCount(9, $auditData);

        $this->assertArrayHasKey('old_values', $auditData);
        $this->assertArrayHasKey('new_values', $auditData);
        $this->assertArrayHasKey('event', $auditData);
        $this->assertArrayHasKey('auditable_id', $auditData);
        $this->assertArrayHasKey('auditable_type', $auditData);
        $this->assertArrayHasKey('user_id', $auditData);
        $this->assertArrayHasKey('url', $auditData);
        $this->assertArrayHasKey('ip_address', $auditData);
        $this->assertArrayHasKey('created_at', $auditData);

        // Modified Auditable attributes
        $this->assertCount(2, $auditData['new_values']);

        $this->assertArrayHasKey('title', $auditData['new_values']);
        $this->assertArrayHasKey('published', $auditData['new_values']);
    }

    /**
     * Test the toAudit() method to PASS (with Auditable timestamps).
     *
     * @return void
     */
    public function testToAuditPassWithAuditableTimestamps()
    {
        Config::set('audit.user.resolver', function () {
            return rand(1, 256);
        });

        $model = new AuditableModelStub();
        $this->setAuditableTestAttributes($model);

        // Include the created/updated timestamps in new_values array
        $model->enableTimestampAuditing();

        $model->setAuditEvent('created');
        $auditData = $model->toAudit();

        // Audit attributes
        $this->assertCount(9, $auditData);

        $this->assertArrayHasKey('old_values', $auditData);
        $this->assertArrayHasKey('new_values', $auditData);
        $this->assertArrayHasKey('event', $auditData);
        $this->assertArrayHasKey('auditable_id', $auditData);
        $this->assertArrayHasKey('auditable_type', $auditData);
        $this->assertArrayHasKey('user_id', $auditData);
        $this->assertArrayHasKey('url', $auditData);
        $this->assertArrayHasKey('ip_address', $auditData);
        $this->assertArrayHasKey('created_at', $auditData);

        // Modified Auditable attributes
        $this->assertCount(5, $auditData['new_values']);

        $this->assertArrayHasKey('title', $auditData['new_values']);
        $this->assertArrayHasKey('content', $auditData['new_values']);
        $this->assertArrayHasKey('published', $auditData['new_values']);
        $this->assertArrayHasKey('created_at', $auditData['new_values']);
        $this->assertArrayHasKey('updated_at', $auditData['new_values']);
    }

    /**
     * Test the toAudit() method to PASS (visible strict mode).
     *
     * @return void
     */
    public function testToAuditPassVisibleStrictMode()
    {
        Config::set('audit.user.resolver', function () {
            return rand(1, 256);
        });

        $model = new AuditableModelStub();
        $this->setAuditableTestAttributes($model);

        // Strict auditing enabled
        $model->enableStrictAuditing();

        // Set visible
        $model->setVisible([
            'title',
            'content',
        ]);

        $model->setAuditEvent('created');
        $auditData = $model->toAudit();

        // Audit attributes
        $this->assertCount(9, $auditData);

        $this->assertArrayHasKey('old_values', $auditData);
        $this->assertArrayHasKey('new_values', $auditData);
        $this->assertArrayHasKey('event', $auditData);
        $this->assertArrayHasKey('auditable_id', $auditData);
        $this->assertArrayHasKey('auditable_type', $auditData);
        $this->assertArrayHasKey('user_id', $auditData);
        $this->assertArrayHasKey('url', $auditData);
        $this->assertArrayHasKey('ip_address', $auditData);
        $this->assertArrayHasKey('created_at', $auditData);

        // Modified Auditable attributes
        $this->assertCount(2, $auditData['new_values']);

        $this->assertArrayHasKey('title', $auditData['new_values']);
        $this->assertArrayHasKey('content', $auditData['new_values']);
    }

    /**
     * Test the toAudit() method to PASS (hidden strict mode).
     *
     * @return void
     */
    public function testToAuditPassHiddenStrictMode()
    {
        Config::set('audit.user.resolver', function () {
            return rand(1, 256);
        });

        $model = new AuditableModelStub();
        $this->setAuditableTestAttributes($model);

        // Strict auditing enabled
        $model->enableStrictAuditing();

        // Set hidden
        $model->setHidden([
            'content',
        ]);

        $model->setAuditEvent('created');
        $auditData = $model->toAudit();

        // Audit attributes
        $this->assertCount(9, $auditData);

        $this->assertArrayHasKey('old_values', $auditData);
        $this->assertArrayHasKey('new_values', $auditData);
        $this->assertArrayHasKey('event', $auditData);
        $this->assertArrayHasKey('auditable_id', $auditData);
        $this->assertArrayHasKey('auditable_type', $auditData);
        $this->assertArrayHasKey('user_id', $auditData);
        $this->assertArrayHasKey('url', $auditData);
        $this->assertArrayHasKey('ip_address', $auditData);
        $this->assertArrayHasKey('created_at', $auditData);

        // Modified Auditable attributes
        $this->assertCount(2, $auditData['new_values']);

        $this->assertArrayHasKey('title', $auditData['new_values']);
        $this->assertArrayHasKey('published', $auditData['new_values']);
    }

    /**
     * Test the getAuditableEvents() method to PASS (default values).
     *
     * @return void
     */
    public function testGetAuditableEventsPassDefault()
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
    public function testGetAuditableEventsPassCustom()
    {
        $model = new AuditableModelStub();

        $model->auditableEvents = [
            'created',
        ];

        $events = $model->getAuditableEvents();

        $this->assertCount(1, $events);
    }

    /**
     * Test the transformAudit() method to PASS.
     *
     * @return void
     */
    public function testTransformAuditPass()
    {
        $model = new AuditableModelStub();

        $data = $model->transformAudit([]);

        $this->assertEquals([], $data);
    }

    /**
     * Test the getAuditDriver() method to PASS (default).
     *
     * @return void
     */
    public function testGetAuditDriverDefaultPass()
    {
        $model = new AuditableModelStub();

        $this->assertNull($model->getAuditDriver());
    }

    /**
     * Test the getAuditDriver() method to PASS (custom).
     *
     * @return void
     */
    public function testGetAuditDriverCustomPass()
    {
        $model = new AuditableModelStub();

        $model->setAuditDriver('database');

        $this->assertEquals('database', $model->getAuditDriver());
    }

    /**
     * Test the getAuditThreshold() method to PASS (default).
     *
     * @return void
     */
    public function testGetAuditThresholdDefaultPass()
    {
        $model = new AuditableModelStub();

        $this->assertEquals(0, $model->getAuditThreshold());
    }

    /**
     * Test the getAuditThreshold() method to PASS (custom).
     *
     * @return void
     */
    public function testGetAuditThresholdCustomPass()
    {
        $model = new AuditableModelStub();

        $model->setAuditThreshold(100);

        $this->assertEquals(100, $model->getAuditThreshold());
    }
}
