<?php

namespace OwenIt\Auditing\Events;

class AuditReport
{
    /**
     * The auditable entity.
     *
     * @var mixed
     */
    public $auditable;

    /**
     * The auditor name.
     *
     * @var string
     */
    public $auditor;

    /**
     * The report response.
     *
     * @var mixed
     */
    public $report;

    /**
     * Create a new event instance.
     *
     * @param mixed  $auditable
     * @param string $auditor
     *
     * @return void
     */
    public function __construct($auditable, $auditor, $report = null)
    {
        $this->report = $report;

        $this->auditor = $auditor;

        $this->auditable = $auditable;
    }
}
