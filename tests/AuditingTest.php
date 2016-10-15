<?php

use OwenIt\Auditing\Auditing;
use Illuminate\Database\Eloquent\Model;

class AuditingTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testItGetsCallbackMethos()
    {
    	$auditing = new Auditing();

    	$auditing->auditable = new EloquentModelStub();
    	$auditing->new = ['title' => 'Auditing'];

    	$property = $auditing->resolveCustomMessage('The title was defined as {new.title}.');
    	$defaultValue = $auditing->resolveCustomMessage('The title was defined as {new.realtitle|no title}.');
    	$callbackMethod = $auditing->resolveCustomMessage('The title was defined as {||getNewTitle}.');

    	$this->assertEquals("The title was defined as Auditing.", $property);
    	$this->assertEquals('The title was defined as no title.', $defaultValue);
    	$this->assertEquals('The title was defined as awesome.', $callbackMethod);
    }

}

class EloquentModelStub extends Model
{
    public function getNewTitle($stub)
    {
    	return 'awesome';
    }
}
