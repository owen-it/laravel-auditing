<?php

namespace OwenIt\Auditing\Concerns;

trait ExcludesAuditAttributes
{

    /**
     * @return array
     */
    public function getAuditExclude(): array
    {
        return $this->auditExclude ?? [];
    }

}