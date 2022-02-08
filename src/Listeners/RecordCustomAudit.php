<?php

namespace OwenIt\Auditing\Listeners;

use OwenIt\Auditing\Facades\Auditor;

class RecordCustomAudit
{
    public function handle(\OwenIt\Auditing\Contracts\Auditable $model)
    {
        Auditor::execute($model);
    }
}
