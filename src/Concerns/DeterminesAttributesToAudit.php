<?php

namespace OwenIt\Auditing\Concerns;

use Illuminate\Support\Facades\Config;

trait DeterminesAttributesToAudit
{
    protected array|null $resolvedExcludedAttributes = null;

    /**
     * Resolve the Auditable attributes to exclude from the Audit.
     */
    protected function resolveAuditExclusions(): array
    {
        if (is_null($this->resolvedExcludedAttributes)) {
            $excludedAttributes = $this->getAuditExclude();

            // When in strict mode, hidden and non-visible attributes are excluded
            if ($this->getAuditStrict()) {
                // Hidden attributes
                $excludedAttributes = array_merge($excludedAttributes, $this->hidden);
            }

            if (! empty($this->getVisible())) {
                $invisible = array_diff(array_keys($this->attributes), $this->getVisible());

                $excludedAttributes = array_merge($excludedAttributes, $invisible);
            }

            // Exclude Timestamps
            if (! $this->shouldAuditTimestamps()) {
                if ($this->getCreatedAtColumn()) {
                    $excludedAttributes[] = $this->getCreatedAtColumn();
                }
                if ($this->getUpdatedAtColumn()) {
                    $excludedAttributes[] = $this->getUpdatedAtColumn();
                }
                if (method_exists($this, 'getDeletedAtColumn')) {
                    $excludedAttributes[] = $this->getDeletedAtColumn();
                }
            }
            $this->resolvedExcludedAttributes = $excludedAttributes;
        }

        return $this->resolvedExcludedAttributes;
    }

    public function getAuditExclude(): array
    {
        return $this->auditExclude ?? Config::get('audit.exclude', []);
    }

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
