<?php

use Mockery as m;

class Auditingest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_it_gets_the_table_name()
    {
        $auditing = m::mock('OwenIt\Auditing\Auditing');

        $auditing->shouldReceive('getCustomMessage')->once()
                 ->withNoArgs(AuditingModel::class)->andReturn('{user.name} {type} this');
    }
}


class AuditingModel
{
}
