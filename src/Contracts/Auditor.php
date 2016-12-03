<?php

namespace OwenIt\Auditing\Contracts;

interface Auditor
{
    /**
     * Get an audit driver instance.
     *
     * @param  Auditable $model
     * @return AuditDriver
     */
    public function auditDriver(Auditable $model);

    /**
     * Perform an audit.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function execute(Auditable $model);
}