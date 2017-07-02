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

interface Auditable
{
    /**
     * Set the Audit event.
     *
     * @param string $event
     *
     * @return Auditable
     */
    public function setAuditEvent($event);

    /**
     * Is the model ready for auditing?
     *
     * @return bool
     */
    public function readyForAuditing();

    /**
     * Return data for an Audit.
     *
     * @throws \RuntimeException
     *
     * @return array
     */
    public function toAudit();

    /**
     * Get the (Auditable) attributes included in audit.
     *
     * @return array
     */
    public function getAuditInclude();

    /**
     * Get the (Auditable) attributes excluded from audit.
     *
     * @return array
     */
    public function getAuditExclude();

    /**
     * Get the strict audit status.
     *
     * @return bool
     */
    public function getAuditStrict();

    /**
     * Get the audit (Auditable) timestamps status.
     *
     * @return bool
     */
    public function getAuditTimestamps();

    /**
     * Get the Audit Driver.
     *
     * @return string
     */
    public function getAuditDriver();

    /**
     * Get the Audit threshold.
     *
     * @return int
     */
    public function getAuditThreshold();

    /**
     * Transform the data before performing an audit.
     *
     * @param array $data
     *
     * @return array
     */
    public function transformAudit(array $data);
}
