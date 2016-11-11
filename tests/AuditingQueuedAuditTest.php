<?php

namespace Tests;

use Mockery;
use OwenIt\Auditing\AuditQueuedModels;

class AuditingQueuedAuditTest extends AbstractTestCase
{
    public function testQueueAudit()
    {
        $job = new AuditQueuedModels('auditable');

        $manager = Mockery::mock('OwenIt\Auditing\AuditorManager');
        $manager->shouldReceive('audit')->once()->with('auditable');

        $job->handle($manager);
    }
}
