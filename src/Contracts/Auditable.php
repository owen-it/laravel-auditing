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
}
