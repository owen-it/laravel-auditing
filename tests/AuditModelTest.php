<?php

namespace OwenIt\Auditing\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Models\Audit;

class AuditModelTest extends AbstractTestCase
{
    public function testGetModifiedPass()
    {
        $audit = new Audit();

        $audit->auditable = new AuditableModel();
        $audit->old = [];
        $audit->new = [
            'title' => 'Auditing',
        ];

        $modified = [
            'title' => [
                'new' => 'Auditing',
            ],
        ];

        $this->assertEquals($modified, $audit->getModified());
    }

    public function testGetConfiguredTable()
    {
        $this->setConfigTable('my_audits', 'audits');

        $audit = new Audit();
        $table = $audit->getTable();

        $this->assertEquals('my_audits', $table);
    }

    public function testGetDefaultTable()
    {
        $this->setConfigTable(null, 'audits');

        $audit = new Audit();
        $table = $audit->getTable();

        $this->assertEquals('audits', $table);
    }

    public function setConfigTable($table, $default)
    {
        Config::shouldReceive('get')
            ->once()
            ->with('auditing.table', $default)
            ->andReturn($table ?: $default);
    }
}

class AuditableModel extends Model implements AuditableContract
{
    use Auditable;
}
