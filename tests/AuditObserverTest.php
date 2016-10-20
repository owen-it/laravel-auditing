<?php

namespace Tests;

use Mockery;
use OwenIt\Auditing\AuditObserver;

class AuditObserverTest extends AbstractTestCase
{
    public function test_saving_handler_prepare_audit()
    {
        $observer = new AuditObserver();
        $model = Mockery::mock();
        $model->shouldReceive('prepareAudit');
        $observer->saving($model);
    }

    public function test_created_handler_audit_creation()
    {
        $observer = new AuditObserver();
        $model = Mockery::mock();
        $model->shouldReceive('auditCreation');
        $observer->created($model);
    }

    public function test_saved_handler_audit_update()
    {
        $observer = new AuditObserver();
        $model = Mockery::mock();
        $model->shouldReceive('auditUpdate');
        $observer->saved($model);
    }

    public function test_deleted_handler_audit_deletion()
    {
        $observer = new AuditObserver();
        $model = Mockery::mock();
        $model->shouldReceive('prepareAudit');
        $model->shouldReceive('auditDeletion');
        $observer->deleted($model);
    }
}
