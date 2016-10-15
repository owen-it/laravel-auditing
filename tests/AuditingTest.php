<?php

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditing;

class AuditingTest extends PHPUnit_Framework_TestCase
{
    protected $db;

    public function setUp()
    {
        $this->db = new DB;

        $this->db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $this->db->addConnection([
            'driver' => 'sqlite',
            'read' => [
                'database'  => ':memory:',
            ],
            'write' => [
                'database'  => ':memory:',
            ],
        ], 'read_write');

        $this->db->setAsGlobal();
    }

    public function tearDown()
    {
        m::close();
    }

    public function testItGetsCallbackMethos()
    {
    	$auditing = new Auditing();

        $model = Mockery::mock();
        $model->shouldReceive('getNewTitle')->once()->andReturn('awesome');;

    	$auditing->auditable = $model;
    	$message = $auditing->resolveCustomMessage('The title was defined as {||getNewTitle}.');

    	$this->assertEquals('The title was defined as awesome', $message);
    }
}
