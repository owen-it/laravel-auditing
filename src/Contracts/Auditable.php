<?php

namespace OwenIt\Auditing\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Auditable
{
    /**
     * Auditable Model audits.
     *
     * @return MorphMany<\OwenIt\Auditing\Models\Audit>
     */
    public function audits(): MorphMany;

    /**
     * Set the Audit event.
     */
    public function setAuditEvent(string $event): Auditable;

    /**
     * Get the Audit event that is set.
     *
     * @return string|null
     */
    public function getAuditEvent();

    /**
     * Get the events that trigger an Audit.
     */
    public function getAuditEvents(): array;

    /**
     * Is the model ready for auditing?
     */
    public function readyForAuditing(): bool;

    /**
     * Return data for an Audit.
     *
     * @throws \OwenIt\Auditing\Exceptions\AuditingException
     */
    public function toAudit(): array;

    /**
     * Get the (Auditable) attributes included in audit.
     */
    public function getAuditInclude(): array;

    /**
     * Get the (Auditable) attributes excluded from audit.
     */
    public function getAuditExclude(): array;

    /**
     * Get the strict audit status.
     */
    public function getAuditStrict(): bool;

    /**
     * Get the audit (Auditable) timestamps status.
     */
    public function shouldAuditTimestamps(): bool;

    /**
     * Get the Audit Driver.
     *
     * @return string|null
     */
    public function getAuditDriver();

    /**
     * Get the Audit threshold.
     */
    public function getAuditThreshold(): int;

    /**
     * Get the Attribute modifiers.
     */
    public function getAttributeModifiers(): array;

    /**
     * Transform the data before performing an audit.
     */
    public function transformAudit(array $data): array;

    /**
     * Generate an array with the model tags.
     */
    public function generateTags(): array;

    /**
     * Transition to another model state from an Audit.
     *
     *
     * @throws \OwenIt\Auditing\Exceptions\AuditableTransitionException
     */
    public function transitionTo(Audit $audit, bool $old = false): Auditable;
}
