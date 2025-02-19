<?php

namespace OwenIt\Auditing\Contracts;

interface AuditDriver
{
    /**
     * Perform an audit.
     */
    public function audit(Auditable $model): ?Audit;

    /**
     * Remove older audits that go over the threshold.
     */
    public function prune(Auditable $model): bool;
}
