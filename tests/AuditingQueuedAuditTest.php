<?php

use OwenIt\Auditing\AuditQueuedModels;

class AuditingQueuedAuditTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testQueueAudit()
    {
    	$job = new AuditQueuedModels('auditable');

    	$manager = Mockery::mock('OwenIt\Auditing\AuditorManager');
    	$manager->shouldReceive('audit')->once()->with('auditable');

    	$job->handle($manager);
    }
}