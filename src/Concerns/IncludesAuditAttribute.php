<?php

namespace OwenIt\Auditing\Concerns;

trait IncludesAuditAttribute
{

    /**
     * @return array
     */
    public function getAuditInclude(): array
    {
        return $this->auditInclude ?? [];
    }

}