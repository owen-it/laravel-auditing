<?php

namespace OwenIt\Auditing\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Auditable
{
    /**
     * Auditable Model audits.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<OwenIt\Auditing\Contracts\Audit>
     */
    public function audits(): MorphMany;

    /**
     * Set the Audit event.
     *
     * @param string $event
     *
     * @return Auditable
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
     *
     * @return array
     */
    public function getAuditEvents(): array;

    /**
     * Is the model ready for auditing?
     *
     * @return bool
     */
    public function readyForAuditing(): bool;

    /**
     * Return data for an Audit.
     *
     * @throws \OwenIt\Auditing\Exceptions\AuditingException
     *
     * @return array
     */
    public function toAudit(): array;

    /**
     * Get the (Auditable) attributes included in audit.
     *
     * @return array
     */
    public function getAuditInclude(): array;

    /**
     * Get the (Auditable) attributes excluded from audit.
     *
     * @return array
     */
    public function getAuditExclude(): array;

    /**
     * Get the strict audit status.
     *
     * @return bool
     */
    public function getAuditStrict(): bool;

    /**
     * Get the audit (Auditable) timestamps status.
     *
     * @return bool
     */
    public function getAuditTimestamps(): bool;

    /**
     * Get the Audit Driver.
     *
     * @return string|null
     */
    public function getAuditDriver();

    /**
     * Get the Audit threshold.
     *
     * @return int
     */
    public function getAuditThreshold(): int;

    /**
     * Get the Attribute modifiers.
     *
     * @return array
     */
    public function getAttributeModifiers(): array;

    /**
     * Transform the data before performing an audit.
     *
     * @param array $data
     *
     * @return array
     */
    public function transformAudit(array $data): array;

    /**
     * Generate an array with the model tags.
     *
     * @return array
     */
    public function generateTags(): array;

    /**
     * Transition to another model state from an Audit.
     *
     * @param Audit $audit
     * @param bool  $old
     *
     * @throws \OwenIt\Auditing\Exceptions\AuditableTransitionException
     *
     * @return Auditable
     */
    public function transitionTo(Audit $audit, bool $old = false): Auditable;
}
