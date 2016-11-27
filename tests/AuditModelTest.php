<?php

namespace OwenIt\Auditing\Tests;

use Illuminate\Support\Facades\Config;
use OwenIt\Auditing\Models\Audit;
use OwenIt\Auditing\Tests\Stubs\AuditableModel;

class AuditModelTest extends AbstractTestCase
{
    public function testGetMetadataModified()
    {
        $audit = new Audit();

        $audit->auditable = new AuditableModel();
        $audit->ip_address = '::1';
        $audit->event = 'created';
        $audit->user_id = 1;
        $audit->url = 'http://www.foo.bar/baz';

        $audit->old = [];
        $audit->new = [
            'title'   => 'Auditing',
            'content' => 'Natum accumsan eu vel.',
        ];

        $metadata = [
            'audit_event'      => $audit->event,
            'audit_url'        => $audit->url,
            'audit_created_at' => $audit->created_at,
            'user_ip_address'  => $audit->ip_address,
            'user_id'          => $audit->user_id,
        ];

        $modified = [
            'title'     => [
                'new' => 'Auditing',
            ],

            'content'   => [
                'new' => 'Natum accumsan eu vel.',
            ],
        ];

        $this->assertEquals($metadata, $audit->getMetadata());
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
