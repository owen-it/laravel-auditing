<?php

namespace OwenIt\Auditing\Tests;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use OwenIt\Auditing\Tests\Stubs\AuditableModel;
use OwenIt\Auditing\Tests\Stubs\AuditableModel2;
use OwenIt\Auditing\Tests\Stubs\AuditableModel3;

class AuditableTest extends AbstractTestCase
{
    public function testWithAuditRespectsWithoutHidden()
    {
        $attributes = [
            'name'     => 'Anterio',
            'password' => '12345',
        ];

        $auditable = new AuditableModel();

        $result = $auditable->cleanHiddenAuditAttributes($attributes);

        $this->assertEquals($attributes, $result);
    }

    public function testWithAuditRespectsWithHidden()
    {
        $attributes = [
            'name'     => 'Anterio',
            'password' => '12345',
        ];

        $auditable = new AuditableModel3();

        $result = $auditable->cleanHiddenAuditAttributes($attributes);

        $expected = [
            'name'     => 'Anterio',
            'password' => null,
        ];

        $this->assertEquals($expected, $result);
    }

    public function testItGetAuditableEvents()
    {
        $model1 = new AuditableModel2();

        $events = [
            'created',
            'updated',
            'deleted',
            'saved',
            'restored',
        ];

        $this->assertEquals($events, $model1->getAuditableEvents());

        $model2 = new AuditableModel3();

        $expected = [
            'created',
        ];

        $this->assertEquals($expected, $model2->getAuditableEvents());
    }

    public function testItIsEventAuditable()
    {
        $model = new AuditableModel();

        $this->assertTrue($model->isEventAuditable('created'));
        $this->assertFalse($model->isEventAuditable('foo'));
    }

    public function testItRunAuditingEnableConsole()
    {
        App::shouldReceive('runningInConsole')
            ->once()
            ->andReturn(true);

        Config::shouldReceive('get')
            ->once()
            ->with('auditing.audit_console')
            ->andReturn(true);

        $model = new AuditableModel();

        $this->assertTrue($model->isAuditEnabled());
    }

    public function testItRunAuditingDisabledConsole()
    {
        $model = new AuditableModel();
        $this->assertTrue($model->isAuditEnabled());
    }
}
