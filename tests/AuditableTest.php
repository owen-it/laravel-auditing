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
use OwenIt\Auditing\Models\Audit;
use OwenIt\Auditing\Tests\Stubs\AuditableDriverStub;
use OwenIt\Auditing\Tests\Stubs\AuditableExcludeStub;
use OwenIt\Auditing\Tests\Stubs\AuditableIncludeStub;
use OwenIt\Auditing\Tests\Stubs\AuditableStrictStub;
use OwenIt\Auditing\Tests\Stubs\AuditableStub;
use OwenIt\Auditing\Tests\Stubs\AuditableThresholdStub;
use OwenIt\Auditing\Tests\Stubs\AuditableTimestampStub;
use OwenIt\Auditing\Tests\Stubs\AuditableTransformStub;
use OwenIt\Auditing\Tests\Stubs\AuditStub;
use OwenIt\Auditing\Tests\Stubs\UserStub;
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
     * Test the toAudit() method to FAIL (Custom attributes method missing).
     *
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Unable to handle "foo" event, customMethod() method missing
     *
     * @return void
     */
    public function testToAuditFailAuditEventMethodMissingCustom()
    {
        $model = Mockery::mock(AuditableStub::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $model->shouldReceive('getAuditableEvents')
            ->andReturn([
                'foo' => 'customMethod',
            ]);

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
        $this->assertCount(9, $auditData);

        $this->assertArrayHasKey('old_values', $auditData);
        $this->assertArrayHasKey('new_values', $auditData);
        $this->assertArrayHasKey('event', $auditData);
        $this->assertArrayHasKey('auditable_id', $auditData);
        $this->assertArrayHasKey('auditable_type', $auditData);
        $this->assertArrayHasKey('user_id', $auditData);
        $this->assertArrayHasKey('url', $auditData);
        $this->assertArrayHasKey('ip_address', $auditData);
        $this->assertArrayHasKey('user_agent', $auditData);

        // Modified Auditable attributes
        $this->assertCount(3, $auditData['new_values']);

        $this->assertArrayHasKey('title', $auditData['new_values']);
        $this->assertArrayHasKey('content', $auditData['new_values']);
        $this->assertArrayHasKey('published', $auditData['new_values']);
    }

    /**
     * Test the toAudit() method to PASS (custom event).
     *
     * @dataProvider providerTestToAuditPassCustomEvent
     *
     * @param string $event
     * @param array  $getAuditableEventsStub
     * @param string $expectedAttributesMethodName
     *
     * @return void
     */
    public function testToAuditPassCustomEvent($event, $getAuditableEventsStub, $expectedAttributesMethodName)
    {
        Config::set('audit.user.resolver', function () {
            return rand(1, 256);
        });

        $model = Mockery::mock(AuditableStub::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->setAuditableTestAttributes($model);

        $model->shouldReceive('getAuditableEvents')
            ->andReturn($getAuditableEventsStub);

        $model->shouldReceive($expectedAttributesMethodName)
            ->once();

        $model->setAuditEvent($event);

        $this->assertTrue($model->readyForAuditing());

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
        $this->assertArrayHasKey('user_agent', $auditData);
    }

    /**
     * Data provider for toAudit() test to PASS (custom event).
     *
     * @return array
     */
    public function providerTestToAuditPassCustomEvent()
    {
        return [
            'Custom event'                                            => ['custom', ['custom'], 'auditCustomAttributes'],
            'Custom attributes method'                                => ['foo', ['foo' => 'myAttributesMethod'], 'myAttributesMethod'],
            'Custom event with wildcard'                              => ['custom', ['cus*'], 'auditCustomAttributes'],
            'Custom event with wildcard and custom attributes method' => ['foo', ['f*' => 'myAttributesMethod'], 'myAttributesMethod'],
        ];
    }

    /**
     * Test the toAudit() method to PASS (custom User foreign key).
     *
     * @return void
     */
    public function testToAuditPassCustomUserForeignKey()
    {
        Config::set('audit.user.foreign_key', 'fk_id');
        Config::set('audit.user.resolver', function () {
            return rand(1, 256);
        });

        $model = new AuditableStub();
        $this->setAuditableTestAttributes($model);

        $model->setAuditEvent('created');

        $this->assertTrue($model->readyForAuditing());

        $auditData = $model->toAudit();

        // Audit attributes
        $this->assertCount(9, $auditData);

        $this->assertArrayHasKey('old_values', $auditData);
        $this->assertArrayHasKey('new_values', $auditData);
        $this->assertArrayHasKey('event', $auditData);
        $this->assertArrayHasKey('auditable_id', $auditData);
        $this->assertArrayHasKey('auditable_type', $auditData);
        $this->assertArrayHasKey('fk_id', $auditData);
        $this->assertArrayHasKey('url', $auditData);
        $this->assertArrayHasKey('ip_address', $auditData);
        $this->assertArrayHasKey('user_agent', $auditData);

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
        Config::set('audit.user.resolver', UserStub::class);

        $model = new AuditableTransformStub();

        $this->setAuditableTestAttributes($model);

        $model->setAuditEvent('created');

        $this->assertTrue($model->readyForAuditing());

        $auditData = $model->toAudit();

        // Audit attributes
        $this->assertCount(10, $auditData);

        $this->assertArrayHasKey('old_values', $auditData);
        $this->assertArrayHasKey('new_values', $auditData);
        $this->assertArrayHasKey('event', $auditData);
        $this->assertArrayHasKey('auditable_id', $auditData);
        $this->assertArrayHasKey('auditable_type', $auditData);
        $this->assertArrayHasKey('user_id', $auditData);
        $this->assertArrayHasKey('url', $auditData);
        $this->assertArrayHasKey('ip_address', $auditData);
        $this->assertArrayHasKey('user_agent', $auditData);
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
        $this->assertCount(9, $auditData);

        $this->assertArrayHasKey('old_values', $auditData);
        $this->assertArrayHasKey('new_values', $auditData);
        $this->assertArrayHasKey('event', $auditData);
        $this->assertArrayHasKey('auditable_id', $auditData);
        $this->assertArrayHasKey('auditable_type', $auditData);
        $this->assertArrayHasKey('user_id', $auditData);
        $this->assertArrayHasKey('url', $auditData);
        $this->assertArrayHasKey('ip_address', $auditData);
        $this->assertArrayHasKey('user_agent', $auditData);

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
        Config::set('audit.user.resolver', UserStub::class);

        $model = new AuditableExcludeStub();
        $this->setAuditableTestAttributes($model);

        $model->setAuditEvent('created');

        $this->assertTrue($model->readyForAuditing());

        $auditData = $model->toAudit();

        $this->assertEquals([
            'content',
        ], $model->getAuditExclude());

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
        $this->assertArrayHasKey('user_agent', $auditData);

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
        $this->assertCount(9, $auditData);

        $this->assertArrayHasKey('old_values', $auditData);
        $this->assertArrayHasKey('new_values', $auditData);
        $this->assertArrayHasKey('event', $auditData);
        $this->assertArrayHasKey('auditable_id', $auditData);
        $this->assertArrayHasKey('auditable_type', $auditData);
        $this->assertArrayHasKey('user_id', $auditData);
        $this->assertArrayHasKey('url', $auditData);
        $this->assertArrayHasKey('ip_address', $auditData);
        $this->assertArrayHasKey('user_agent', $auditData);

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
        Config::set('audit.user.resolver', UserStub::class);

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
        $this->assertCount(9, $auditData);

        $this->assertArrayHasKey('old_values', $auditData);
        $this->assertArrayHasKey('new_values', $auditData);
        $this->assertArrayHasKey('event', $auditData);
        $this->assertArrayHasKey('auditable_id', $auditData);
        $this->assertArrayHasKey('auditable_type', $auditData);
        $this->assertArrayHasKey('user_id', $auditData);
        $this->assertArrayHasKey('url', $auditData);
        $this->assertArrayHasKey('ip_address', $auditData);
        $this->assertArrayHasKey('user_agent', $auditData);

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
        $this->assertCount(9, $auditData);

        $this->assertArrayHasKey('old_values', $auditData);
        $this->assertArrayHasKey('new_values', $auditData);
        $this->assertArrayHasKey('event', $auditData);
        $this->assertArrayHasKey('auditable_id', $auditData);
        $this->assertArrayHasKey('auditable_type', $auditData);
        $this->assertArrayHasKey('user_id', $auditData);
        $this->assertArrayHasKey('url', $auditData);
        $this->assertArrayHasKey('ip_address', $auditData);
        $this->assertArrayHasKey('user_agent', $auditData);

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

    /**
     * Test Audit implementation to PASS (default).
     *
     * @return void
     */
    public function testAuditImplementationPassDefault()
    {
        $model = new AuditableStub();

        $this->assertInstanceOf(Audit::class, $model->audits()->getRelated());
    }

    /**
     * Test Audit implementation to PASS (custom).
     *
     * @return void
     */
    public function testAuditImplementationPassCustom()
    {
        $model = new AuditableStub();

        Config::set('audit.implementation', AuditStub::class);

        $this->assertInstanceOf(AuditStub::class, $model->audits()->getRelated());
    }

    /**
     * Test the isEventAuditable() method to PASS (default).
     *
     * @dataProvider providerTestToAuditPassCustomAttributesMethod
     *
     * @param string $event
     * @param bool   $expected
     * @param array  $getAuditableEventsStub
     *
     * @return void
     */
    public function testIsEventAuditableDefault($event, $expected, $getAuditableEventsStub = null)
    {
        $model = Mockery::mock(AuditableStub::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->setAuditableTestAttributes($model);

        if (isset($getAuditableEventsStub)) {
            $model->shouldReceive('getAuditableEvents')
                ->andReturn($getAuditableEventsStub);
        }

        $this->assertSame($expected, $model->isEventAuditable($event));
    }

    /**
     * Data provider for isEventAuditable() test to PASS (default).
     *
     * @return array
     */
    public function providerTestToAuditPassCustomAttributesMethod()
    {
        return [
            'Default created event'                               => ['created', true],
            'Default updated event'                               => ['updated', true],
            'Default deleted event'                               => ['deleted', true],
            'Custom event'                                        => ['custom', true, ['created', 'custom']],
            'Custom event with custom attributes method'          => ['custom2', true, ['custom2' => 'myMethod']],
            'Custom event with * in name'                         => ['myCustomEvent', true, ['myCustom*']],
            'Custom event with * in name and custom attributes'   => ['customEvent', true, ['custom*' => 'method']],
            'Created event but not present in getAuditableEvents' => ['created', false, ['updated', 'deleted']],
            'Unknown event'                                       => ['other', false],
            'Other unknown event'                                 => ['really', false, ['reallyCreated*']],
        ];
    }
}
