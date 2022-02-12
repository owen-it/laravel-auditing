<?php

namespace OwenIt\Auditing\Concerns;

use Illuminate\Support\Facades\Config;

trait ExcludesAuditAttributes
{
    /**
     *
     * @var array
     */
    protected $auditExclude = ['-'];

    /**
     * @return array
     */
    public function getAuditExclude(): array
    {
        if ($this->auditExclude == ['-']) {
            return Config::get('audit.exclude', []);
        }
        return $this->auditExclude;
    }

    /**
     * @param array $auditExclude
     */
    public function setAuditExclude(array $auditExclude): void
    {
        $this->auditExclude = $auditExclude;
    }
}