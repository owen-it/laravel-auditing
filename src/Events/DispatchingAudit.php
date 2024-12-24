<?php

namespace OwenIt\Auditing\Events;

use OwenIt\Auditing\Contracts\Auditable;

class DispatchingAudit
{
    /**
     * The Auditable model.
     *
     * @var Auditable
     */
    public $model;

    /**
     * Create a new DispatchingAudit event instance.
     */
    public function __construct(Auditable $model)
    {
        $this->model = $model;
    }
}
