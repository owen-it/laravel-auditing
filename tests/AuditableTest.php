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
use OwenIt\Auditing\Tests\Stubs\AuditableDriverStub;
use OwenIt\Auditing\Tests\Stubs\AuditableExcludeStub;
use OwenIt\Auditing\Tests\Stubs\AuditableIncludeStub;
use OwenIt\Auditing\Tests\Stubs\AuditableStrictStub;
use OwenIt\Auditing\Tests\Stubs\AuditableStub;
use OwenIt\Auditing\Tests\Stubs\AuditableThresholdStub;
use OwenIt\Auditing\Tests\Stubs\AuditableTimestampStub;
use OwenIt\Auditing\Tests\Stubs\AuditableTransformStub;
use OwenIt\Auditing\Tests\Stubs\UserResolverStub;
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
        $audit->title = 'How To Audit Eloquent Models';
        $audit->content = 'First step: install the laravel-auditing package.';
        $audit->published = 1;
    }

    /**
     * Test the toAudit() method to FAIL (Invalid audit event).
     *
     * @expectedException        RuntimeException
     * @expectedExceptionMessage A valid audit event has not been set
     *
     * @return void
     */
    public function testToAuditFailInvalidAuditEvent()
    {
        $model = new AuditableStub();

        // Invalid auditable event
        $model->setAuditEvent('foo');

        $this->assertFalse($model->readyForAuditing());

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
        $model = Mockery::mock(AuditableStub::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $model->shouldReceive('isEventAuditable')
            ->andReturn(true);

        $model->setAuditEvent('foo');

        $this->assertTrue($model->readyForAuditing());

        $model->toAudit();
    }

    /**
     * Test the toAudit() method to FAIL (Invalid User id resolver).
     *
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Invalid User resolver, callable or UserResolver FQCN expected
     *
     * @return void
     */
    public function testToAuditFailInvalidUserIdResolver()
    {
        Config::set('audit.user.resolver', null);

        $model = new AuditableStub();

        $model->setAuditEvent('created');

        $this->assertTrue($model->readyForAuditing());

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

        $model = new AuditableStub();
        $this->setAuditableTestAttributes($model);

        $model->setAuditEvent('created');

        $this->assertTrue($model->readyForAuditing());

        $auditData = $model->toAudit();

        // Audit attributes
        $this->assertCount(11, $auditData);

        $this->assertArrayHasKey('old_values', $auditData);
        $this->assertArrayHasKey('new_values', $auditData);
        $this->assertArrayHasKey('event', $auditData);
        $this->assertArrayHasKey('auditable_id', $auditData);
        $this->assertArrayHasKey('auditable_type', $auditData);
        $this->assertArrayHasKey('user_id', $auditData);
        $this->assertArrayHasKey('url', $auditData);
        $this->assertArrayHasKey('ip_address', $auditData);
        $this->assertArrayHasKey('user_agent', $auditData);
        $this->assertArrayHasKey('related_relations_json', $auditData);
        $this->assertArrayHasKey('created_at', $auditData);

        // Modified Auditable attributes
        $this->assertCount(3, $auditData['new_values']);

        $this->assertArrayHasKey('title', $auditData['new_values']);
        $this->assertArrayHasKey('content', $auditData['new_values']);
        $this->assertArrayHasKey('published', $auditData['new_values']);
    }

    /**
     * Test the toAudit() method to PASS (custom transformAudit()).
     *
     * @return void
     */
    public function testToAuditPassCustomTransformAudit()
    {
        Config::set('audit.user.resolver', UserResolverStub::class);

        $model = new AuditableTransformStub();

        $this->setAuditableTestAttributes($model);

        $model->setAuditEvent('created');

        $this->assertTrue($model->readyForAuditing());

        $auditData = $model->toAudit();

        // Audit attributes
        $this->assertCount(12, $auditData);

        $this->assertArrayHasKey('old_values', $auditData);
        $this->assertArrayHasKey('new_values', $auditData);
        $this->assertArrayHasKey('event', $auditData);
        $this->assertArrayHasKey('auditable_id', $auditData);
        $this->assertArrayHasKey('auditable_type', $auditData);
        $this->assertArrayHasKey('user_id', $auditData);
        $this->assertArrayHasKey('url', $auditData);
        $this->assertArrayHasKey('ip_address', $auditData);
        $this->assertArrayHasKey('user_agent', $auditData);
        $this->assertArrayHasKey('related_relations_json', $auditData);
        $this->assertArrayHasKey('created_at', $auditData);
        $this->assertArrayHasKey('foo', $auditData);
    }

    /**
     * Test the toAudit() method to PASS (include attributes).
     *
     * @return void
     */
    public function testToAuditPassIncludeAttributes()
    {
        Config::set('audit.user.resolver', function () {
            return rand(1, 256);
        });

        $model = new AuditableIncludeStub();
        $this->setAuditableTestAttributes($model);

        $model->setAuditEvent('created');

        $this->assertTrue($model->readyForAuditing());

        $auditData = $model->toAudit();

        $this->assertEquals([
            'title',
            'content',
        ], $model->getAuditInclude());

        // Audit attributes
        $this->assertCount(11, $auditData);

        $this->assertArrayHasKey('old_values', $auditData);
        $this->assertArrayHasKey('new_values', $auditData);
        $this->assertArrayHasKey('event', $auditData);
        $this->assertArrayHasKey('auditable_id', $auditData);
        $this->assertArrayHasKey('auditable_type', $auditData);
        $this->assertArrayHasKey('user_id', $auditData);
        $this->assertArrayHasKey('url', $auditData);
        $this->assertArrayHasKey('ip_address', $auditData);
        $this->assertArrayHasKey('user_agent', $auditData);
        $this->assertArrayHasKey('related_relations_json', $auditData);
        $this->assertArrayHasKey('created_at', $auditData);

        // Modified Auditable attributes
        $this->assertCount(2, $auditData['new_values']);

        $this->assertArrayHasKey('title', $auditData['new_values']);
        $this->assertArrayHasKey('content', $auditData['new_values']);
    }

    /**
     * Test the toAudit() method to PASS (exclude attributes).
     *
     * @return void
     */
    public function testToAuditPassExcludeAttributes()
    {
        Config::set('audit.user.resolver', UserResolverStub::class);

        $model = new AuditableExcludeStub();
        $this->setAuditableTestAttributes($model);

        $model->setAuditEvent('created');

        $this->assertTrue($model->readyForAuditing());

        $auditData = $model->toAudit();

        $this->assertEquals([
            'content',
        ], $model->getAuditExclude());

        // Audit attributes
        $this->assertCount(11, $auditData);

        $this->assertArrayHasKey('old_values', $auditData);
        $this->assertArrayHasKey('new_values', $auditData);
        $this->assertArrayHasKey('event', $auditData);
        $this->assertArrayHasKey('auditable_id', $auditData);
        $this->assertArrayHasKey('auditable_type', $auditData);
        $this->assertArrayHasKey('user_id', $auditData);
        $this->assertArrayHasKey('url', $auditData);
        $this->assertArrayHasKey('ip_address', $auditData);
        $this->assertArrayHasKey('user_agent', $auditData);
        $this->assertArrayHasKey('related_relations_json', $auditData);
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

        $model = new AuditableTimestampStub();
        $this->setAuditableTestAttributes($model);

        $model->setAuditEvent('created');

        $this->assertTrue($model->readyForAuditing());

        $auditData = $model->toAudit();

        $this->assertTrue($model->getAuditTimestamps());

        // Audit attributes
        $this->assertCount(11, $auditData);

        $this->assertArrayHasKey('old_values', $auditData);
        $this->assertArrayHasKey('new_values', $auditData);
        $this->assertArrayHasKey('event', $auditData);
        $this->assertArrayHasKey('auditable_id', $auditData);
        $this->assertArrayHasKey('auditable_type', $auditData);
        $this->assertArrayHasKey('user_id', $auditData);
        $this->assertArrayHasKey('url', $auditData);
        $this->assertArrayHasKey('ip_address', $auditData);
        $this->assertArrayHasKey('user_agent', $auditData);
        $this->assertArrayHasKey('related_relations_json', $auditData);
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
        Config::set('audit.user.resolver', UserResolverStub::class);

        $model = new AuditableStrictStub();
        $this->setAuditableTestAttributes($model);

        // Set visible
        $model->setVisible([
            'title',
            'content',
        ]);

        $model->setAuditEvent('created');

        $this->assertTrue($model->readyForAuditing());

        $auditData = $model->toAudit();

        $this->assertTrue($model->getAuditStrict());

        // Audit attributes
        $this->assertCount(11, $auditData);

        $this->assertArrayHasKey('old_values', $auditData);
        $this->assertArrayHasKey('new_values', $auditData);
        $this->assertArrayHasKey('event', $auditData);
        $this->assertArrayHasKey('auditable_id', $auditData);
        $this->assertArrayHasKey('auditable_type', $auditData);
        $this->assertArrayHasKey('user_id', $auditData);
        $this->assertArrayHasKey('url', $auditData);
        $this->assertArrayHasKey('ip_address', $auditData);
        $this->assertArrayHasKey('user_agent', $auditData);
        $this->assertArrayHasKey('related_relations_json', $auditData);
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

        $model = new AuditableStrictStub();
        $this->setAuditableTestAttributes($model);

        // Set hidden
        $model->setHidden([
            'content',
        ]);

        $model->setAuditEvent('created');

        $this->assertTrue($model->readyForAuditing());

        $auditData = $model->toAudit();

        $this->assertTrue($model->getAuditStrict());

        // Audit attributes
        $this->assertCount(11, $auditData);

        $this->assertArrayHasKey('old_values', $auditData);
        $this->assertArrayHasKey('new_values', $auditData);
        $this->assertArrayHasKey('event', $auditData);
        $this->assertArrayHasKey('auditable_id', $auditData);
        $this->assertArrayHasKey('auditable_type', $auditData);
        $this->assertArrayHasKey('user_id', $auditData);
        $this->assertArrayHasKey('url', $auditData);
        $this->assertArrayHasKey('ip_address', $auditData);
        $this->assertArrayHasKey('user_agent', $auditData);
        $this->assertArrayHasKey('related_relations_json', $auditData);
        $this->assertArrayHasKey('created_at', $auditData);

        // Modified Auditable attributes
        $this->assertCount(2, $auditData['new_values']);

        $this->assertArrayHasKey('title', $auditData['new_values']);
        $this->assertArrayHasKey('published', $auditData['new_values']);
    }

    /**
     * Test the getAuditableEvents() method to PASS (default).
     *
     * @return void
     */
    public function testGetAuditableEventsPassDefault()
    {
        $model = new AuditableStub();

        $events = $model->getAuditableEvents();

        $this->assertCount(4, $events);
    }

    /**
     * Test the getAuditableEvents() method to PASS (custom).
     *
     * @return void
     */
    public function testGetAuditableEventsPassCustom()
    {
        $model = new AuditableStub();

        $model->auditableEvents = [
            'created',
        ];

        $events = $model->getAuditableEvents();

        $this->assertCount(1, $events);
    }

    /**
     * Test the getAuditDriver() method to PASS (default).
     *
     * @return void
     */
    public function testGetAuditDriverPassDefault()
    {
        $model = new AuditableStub();

        $this->assertNull($model->getAuditDriver());
    }

    /**
     * Test the getAuditDriver() method to PASS (custom).
     *
     * @return void
     */
    public function testGetAuditDriverPassCustom()
    {
        $model = new AuditableDriverStub();

        $this->assertEquals('database', $model->getAuditDriver());
    }

    /**
     * Test the getAuditThreshold() method to PASS (default).
     *
     * @return void
     */
    public function testGetAuditThresholdPassDefault()
    {
        $model = new AuditableStub();

        $this->assertEquals(0, $model->getAuditThreshold());
    }

    /**
     * Test the getAuditThreshold() method to PASS (custom).
     *
     * @return void
     */
    public function testGetAuditThresholdPassCustom()
    {
        $model = new AuditableThresholdStub();

        $this->assertEquals(100, $model->getAuditThreshold());
    }
}
