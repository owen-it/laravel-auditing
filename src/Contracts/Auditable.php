<?php

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
     * Return data for an Audit.
     *
     * @throws \RuntimeException
     * @return array
     */
    public function toAudit();

    /**
     * Get the Audit Drivers.
     *
     * @return array|string
     */
    public function getAuditDrivers();

    /**
     * Clear the oldest audits if given a limit.
     *
     * @return void
     */
    public function clearOlderAudits();

    /**
     * Allows transforming the audit data
     * before being passed to an Auditor.
     *
     * @param array $data
     *
     * @return array
     */
    public function transformAudit(array $data);
}
