<?php

namespace OwenIt\Auditing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use OwenIt\Auditing\Contracts\Auditable;

class DispatchAudit
{
    use Dispatchable;

    /**
     * The Auditable model.
     *
     * @var Auditable
     */
    public $model;

    /**
     * Create a new DispatchAudit event instance.
     */
    public function __construct(Auditable $model)
    {
        $this->model = $model;
    }
}
