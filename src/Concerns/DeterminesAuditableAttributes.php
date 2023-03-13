<?php

namespace OwenIt\Auditing\Concerns;

use Illuminate\Support\Facades\Config;

trait DeterminesAuditableAttributes
{
    /**
     * Resolve the Auditable attributes to exclude from the Audit.
     */
    protected function resolveAuditExclusions(): array
    {
        $excludedAttributes = $this->getAuditExclude();

        // When in strict mode, hidden and non visible attributes are excluded
        if ($this->getAuditStrict()) {
            // Hidden attributes
            $excludedAttributes = array_merge($excludedAttributes, $this->hidden);

            // Non visible attributes
            if ($this->visible) {
                $invisible = array_diff(array_keys($this->attributes), $this->visible);

                $excludedAttributes = array_merge($excludedAttributes, $invisible);
            }
        }

        // Exclude Timestamps
        if (!$this->shouldAuditTimestamps()) {
            array_push($excludedAttributes, $this->getCreatedAtColumn(), $this->getUpdatedAtColumn());
            if (method_exists($this, 'getDeletedAtColumn')) {
                $excludedAttributes[] = $this->getDeletedAtColumn();
            }
        }

        return $excludedAttributes;
    }

    /**
     * @return array
     */
    public function getAuditExclude(): array
    {
        return $this->auditExclude ?? Config::get('audit.exclude', []);
    }

    /**
     * @return array
     */
    public function getAuditInclude(): array
    {
        return $this->auditInclude ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function shouldAuditTimestamps(): bool
    {
        return $this->auditTimestamps ?? Config::get('audit.timestamps', false);
    }
}
