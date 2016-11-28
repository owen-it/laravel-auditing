<?php

namespace OwenIt\Auditing\Contracts;

interface Auditable
{
    /**
     * Prepare audit model.
     *
     * @return void
     */
    public function prepareAudit();

    /**
     * Audit creation.
     *
     * @return void
     */
    public function auditCreation();

    /**
     * Audit updated.
     *
     * @return void
     */
    public function auditUpdate();

    /**
     * Audit deletion.
     *
     * @return void
     */
    public function auditDeletion();

    /**
     * Return data for Audit.
     *
     * @return array
     */
    public function toAudit();

    /**
     * Get the Auditors.
     *
     * @return array
     */
    public function getAuditors();

    /**
     * Clear the oldest audit's if given a limit.
     *
     * @return void
     */
    public function clearOlderAudits();

    /**
     * Allows transforming the audit data
     * before it's passed to the database.
     *
     * @param array $data
     *
     * @return array
     */
    public function transformAudit(array $data);
}
