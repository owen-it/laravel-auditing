<?php

namespace OwenIt\Auditing\Events;

use OwenIt\Auditing\Contracts\Auditable;

class AuditReview
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
     * Create a new event instance.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     * @param string                               $auditor
     */
    public function __construct(Auditable $model, $auditor)
    {
        $this->model = $model;
        $this->auditor = $auditor;
    }
}
