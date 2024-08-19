<?php

namespace OwenIt\Auditing\Listeners;

use OwenIt\Auditing\Events\AuditCustom;
use OwenIt\Auditing\Facades\Auditor;

class RecordCustomAudit
{
    public function handle(AuditCustom $event)
    {
        Auditor::execute($event->model);
    }
}
