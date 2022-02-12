<?php

namespace OwenIt\Auditing\Concerns;

trait IncludesAuditAttribute
{
    /**
     *
     * @var array
     */
    protected $auditInclude = [];

    /**
     * @return array
     */
    public function getAuditInclude(): array
    {
        return $this->auditInclude;
    }

    /**
     * @param array $auditInclude
     */
    public function setAuditInclude(array $auditInclude): void
    {
        $this->auditInclude = $auditInclude;
    }
}