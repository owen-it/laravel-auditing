<?php

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;

class AuditableTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

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

    public function testItGetsLogCustomMessage()
    {
        $logCustomMessage = ModelAuditableTestCustomsValues::$logCustomMessage;

        $this->assertEquals('{user.name} {type} a post {elapsed_time}', $logCustomMessage);
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

    public static $logCustomMessage = '{user.name} {type} a post {elapsed_time}';
}

class ModelAuditableTestConfigs
{
    use Auditable;

    public static $auditRespectsHidden = true;
}
