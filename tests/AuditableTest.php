<?php

namespace OwenIt\Auditing\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class AuditableTest extends AbstractTestCase
{
    public function testWithAuditRespectsWithoutHidden()
    {
        $attributes = [
            'name'     => 'Anterio',
            'password' => '12345',
        ];

        $auditable = new AuditableModel1();

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
        $model = new AuditableModel1();

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

        $model = new AuditableModel1();

        $this->assertTrue($model->isAuditEnabled());
    }

    public function testItRunAuditingDisabledConsole()
    {
        $model = new AuditableModel1();
        $this->assertTrue($model->isAuditEnabled());
    }
}

class AuditableModel1 implements AuditableContract
{
    use Auditable;
}

class AuditableModel2 implements AuditableContract
{
    use Auditable;

    public static $auditRespectsHidden = true;
}

class AuditableModel3 extends Model implements AuditableContract
{
    use Auditable;

    protected $hidden = [
        'password',
    ];

    protected $auditRespectsHidden = true;

    protected $auditableEvents = [
        'created',
    ];
}
