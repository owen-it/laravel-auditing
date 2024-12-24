<?php

namespace OwenIt\Auditing\Contracts;

interface Auditor
{
    /**
     * Get an audit driver instance.
     */
    public function auditDriver(Auditable $model): AuditDriver;

    /**
     * Perform an audit.
     *
     * @return void
     */
    public function execute(Auditable $model);
}
