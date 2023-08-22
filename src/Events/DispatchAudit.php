<?php

namespace OwenIt\Auditing\Events;

use OwenIt\Auditing\Contracts\Auditable;

class DispatchAudit
{
    /**
     * The Auditable model.
     *
     * @var \OwenIt\Auditing\Contracts\Auditable
     */
    public $model;

    /**
     * Create a new DispatchAudit event instance.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     * @param array $old
     * @param array $new
     */
    public function __construct(Auditable $model)
    {
        $this->model = $model;
    }
}
