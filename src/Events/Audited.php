<?php

namespace OwenIt\Auditing\Events;

use OwenIt\Auditing\Contracts\Audit;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\AuditDriver;

class Audited
{
    /**
     * The Auditable model.
     *
     * @var \OwenIt\Auditing\Contracts\Auditable
     */
    public $model;

    /**
     * Audit driver.
     *
     * @var \OwenIt\Auditing\Contracts\AuditDriver
     */
    public $driver;

    /**
     * The Audit model.
     *
     * @var \OwenIt\Auditing\Contracts\Audit|null
     */
    public $audit;

    /**
     * Create a new Audited event instance.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable   $model
     * @param \OwenIt\Auditing\Contracts\AuditDriver $driver
     * @param \OwenIt\Auditing\Contracts\Audit|null  $audit
     */
    public function __construct(Auditable $model, AuditDriver $driver, ?Audit $audit = null)
    {
        $this->model = $model;
        $this->driver = $driver;
        $this->audit = $audit;
    }
}
