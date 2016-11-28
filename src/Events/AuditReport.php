<?php

namespace OwenIt\Auditing\Events;

use OwenIt\Auditing\Contracts\Auditable;

class AuditReport
{
    /**
     * The Auditable model.
     *
     * @var \OwenIt\Auditing\Contracts\Auditable
     */
    public $model;

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
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     * @param string                               $auditor
     * @param mixed                                $report
     */
    public function __construct(Auditable $model, $auditor, $report = null)
    {
        $this->model = $model;
        $this->auditor = $auditor;
        $this->report = $report;
    }
}
