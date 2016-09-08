<?php

use OwenIt\Auditing\Auditable;

class AuditableTest extends PHPUnit_Framework_TestCase
{
    public function test_it_gets_the_table_name()
    {
        $logCustomMessage = ModelAuditable::$logCustomMessage;

        $this->assertEquals('{user.name} {type} a post {elapsed_time}', $logCustomMessage);
    }
}

class ModelAuditable
{
    use Auditable;

    public static $logCustomMessage = '{user.name} {type} a post {elapsed_time}';
}
