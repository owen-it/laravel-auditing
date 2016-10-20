<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use OwenIt\Auditing\Auditable;

class AuditableTest extends AbstractTestCase
{
    public function testItGetsTransformAudit()
    {
        $attributes = ['name' => 'Anterio', 'password' => '12345'];

        $model = new ModelAuditableTestRaw();
        $result = $model->transformAudit($attributes);

        $this->assertEquals($attributes, $result);
    }

    public function testWithAuditRespectsWithoutHidden()
    {
        $attributes = ['name' => 'Anterio', 'password' => '12345'];

        $auditable = new ModelAuditableTestRaw();

        $result = $auditable->cleanHiddenAuditAttributes($attributes);

        $this->assertEquals($attributes, $result);
    }

    public function testWithAuditRespectsWithHidden()
    {
        $attributes = ['name' => 'Anterio', 'password' => '12345'];

        $auditable = new ModelAuditableTestCustomsValues();

        $result = $auditable->cleanHiddenAuditAttributes($attributes);

        $this->assertEquals(['name' => 'Anterio', 'password' => null], $result);
    }

    public function testItGetAuditableTypes()
    {
        $model1 = new ModelAuditableTestConfigs();

        $types = [
                'created', 'updated', 'deleted',
                'saved', 'restored',
        ];

        $this->assertEquals($types, $model1->getAuditableTypes());

        $model2 = new ModelAuditableTestCustomsValues();

        $this->assertEquals(['created'], $model2->getAuditableTypes());
    }

    public function testItIsTypeAuditable()
    {
        $model = new ModelAuditableTestRaw();

        $this->assertTrue($model->isTypeAuditable('created'));
        $this->assertFalse($model->isTypeAuditable('foo'));
    }

    public function testItGetsLogCustomMessage()
    {
        $logCustomMessage = ModelAuditableTestCustomsValues::$logCustomMessage;

        $this->assertEquals('{user.name} {type} a post {elapsed_time}', $logCustomMessage);
    }

    public function testItRunAuditingEnableConsole()
    {
        App::shouldReceive('runningInConsole')->once()->andReturn(true);
        Config::shouldReceive('get')->once()->with('auditing.audit_console')->andReturn(true);

        $model = new ModelAuditableTestRaw();

        $this->assertTrue($model->isAuditEnabled());
    }

    public function testItRunAuditingDisabledConsole()
    {
        $model = new ModelAuditableTestRaw();
        $this->assertTrue($model->isAuditEnabled());
    }
}

class ModelAuditableTestRaw
{
    use Auditable;
}

class ModelAuditableTestCustomsValues extends Model
{
    use Auditable;

    protected $hidden = ['password'];

    protected $auditRespectsHidden = true;

    protected $auditableTypes = ['created'];

    public static $logCustomMessage = '{user.name} {type} a post {elapsed_time}';
}

class ModelAuditableTestConfigs
{
    use Auditable;

    public static $auditRespectsHidden = true;
}
