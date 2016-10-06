<?php

use OwenIt\Auditing\Auditable;

class AuditableTest extends PHPUnit_Framework_TestCase
{
	public function tearDown()
    {
        Mockery::close();
    }

	public function testWithAuditRespectsHidden()
	{
		$auditableMock = Mockery::mock(ModelAuditableTestRaw::class.'[isAuditRespectsHidden]');
        
        $auditableMock->shouldReceive('isAuditRespectsHidden')->andReturn(false);
	}

	public function testWithoutAuditRespectsHidden()
	{
		$auditableMock = Mockery::mock(ModelAuditableTestConfigs::class.'[isAuditRespectsHidden]');
        
        $auditableMock->shouldReceive('isAuditRespectsHidden')->andReturn(true);
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

class ModelAuditableTestCustomsValues
{
    use Auditable;

    public static $logCustomMessage = '{user.name} {type} a post {elapsed_time}';
}

class ModelAuditableTestConfigs
{
    use Auditable;

    public static $auditRespectsHidden = true;
}
