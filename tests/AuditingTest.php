<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use OwenIt\Auditing\Auditing;

class AuditingTest extends AbstractTestCase
{
    public function testItGetsCustomMessages()
    {
        $auditing = new Auditing();

        $auditing->auditable = new EloquentModelStub();
        $auditing->new = ['title' => 'Auditing'];

        $property = $auditing->resolveCustomMessage('The title was defined as {new.title}.');
        $defaultValue = $auditing->resolveCustomMessage('The title was defined as {new.realtitle|no title}.');
        $callbackMethod = $auditing->resolveCustomMessage('The title was defined as {||getNewTitle}.');

        $this->assertEquals('The title was defined as Auditing.', $property);
        $this->assertEquals('The title was defined as no title.', $defaultValue);
        $this->assertEquals('The title was defined as awesome.', $callbackMethod);
    }

    public function testItGetTableInConfig()
    {
        $this->setConfigTable('auditing');

        $auditing = new Auditing();
        $table = $auditing->getTable();

        $this->assertEquals('auditing', $table);
    }

    public function testItGetTableWithoutConfig()
    {
        $this->setConfigTable(null);

        $auditing = new Auditing();
        $table = $auditing->getTable();

        $this->assertEquals('audits', $table);
    }

    public function setConfigTable($table)
    {
        Config::shouldReceive('get')
            ->once()
            ->with('auditing.table')
            ->andReturn($table);
    }
}

class EloquentModelStub extends Model
{
    public function getNewTitle($stub)
    {
        return 'awesome';
    }
}
