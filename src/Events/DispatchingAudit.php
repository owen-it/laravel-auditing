<?php

namespace OwenIt\Auditing\Events;

use OwenIt\Auditing\Contracts\Auditable;

class DispatchingAudit
{
    /**
     * The Auditable model.
     *
     * @var \OwenIt\Auditing\Contracts\Auditable
     */
    public $model;

    /**
     * Create a new DispatchingAudit event instance.
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
