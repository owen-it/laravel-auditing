<?php
/**
 * This file is part of the Laravel Auditing package.
 *
 * @author     Antério Vieira <anteriovieira@gmail.com>
 * @author     Quetzy Garcia  <quetzyg@altek.org>
 * @author     Raphael França <raphaelfrancabsb@gmail.com>
 * @copyright  2015-2017
 *
 * For the full copyright and license information,
 * please view the LICENSE.md file that was distributed
 * with this source code.
 */

namespace OwenIt\Auditing\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Auditable
{
    /**
     * Auditable Model audits.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
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
     * Is the model ready for auditing?
     *
     * @return bool
     */
    public function readyForAuditing(): bool;

    /**
     * Return data for an Audit.
     *
     * @throws \RuntimeException
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
     * Transform the data before performing an audit.
     *
     * @param array $data
     *
     * @return array
     */
    public function transformAudit(array $data): array;

    /**
     * Get an array with the model tags.
     *
     * @return array
     */
    public function getTags(): array;

    /**
     * Transition the model state through an Audit.
     *
     * @param Audit $audit
     * @param array $exclude
     *
     * @return bool
     */
    public function transitionThrough(Audit $audit, array $exclude = []): bool;
}
