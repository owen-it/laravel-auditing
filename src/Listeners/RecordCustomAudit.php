<?php

namespace OwenIt\Auditing\Listeners;

use OwenIt\Auditing\Events\AuditCustom;
use OwenIt\Auditing\Facades\Auditor;

class RecordCustomAudit
{
    public function handle(AuditCustom $event): void
    {
        if (method_exists($event->model, 'isAuditingEnabled') && ! $event->model::isAuditingEnabled()) {
            return;
        }

        Auditor::execute($event->model);
    }
}
