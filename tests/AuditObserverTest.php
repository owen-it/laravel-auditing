<?php

namespace OwenIt\Auditing\Tests;

use Mockery;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Observers\Audit;

class AuditObserverTest extends AbstractTestCase
{
    public function test_saving_handler_prepare_audit()
    {
        $observer = new Audit();
        $model = Mockery::mock(Auditable::class);
        $model->shouldReceive('prepareAudit');
        $observer->saving($model);
    }

    public function test_created_handler_audit_creation()
    {
        $observer = new Audit();
        $model = Mockery::mock(Auditable::class);
        $model->shouldReceive('auditCreation');
        $observer->created($model);
    }

    public function test_saved_handler_audit_update()
    {
        $observer = new Audit();
        $model = Mockery::mock(Auditable::class);
        $model->shouldReceive('auditUpdate');
        $observer->saved($model);
    }

    public function test_deleted_handler_audit_deletion()
    {
        $observer = new Audit();
        $model = Mockery::mock(Auditable::class);
        $model->shouldReceive('prepareAudit');
        $model->shouldReceive('auditDeletion');
        $observer->deleted($model);
    }
}
