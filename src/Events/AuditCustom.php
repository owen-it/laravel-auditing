<?php

namespace OwenIt\Auditing\Events;

use OwenIt\Auditing\Contracts\Auditable;

class AuditCustom
{
    /**
     * The Auditable model.
     *
     * @var \OwenIt\Auditing\Contracts\Auditable
     */
    public $model;

    /**
     * Create a new Auditing event instance.
     */
    public function __construct(Auditable $model)
    {
        $this->model = $model;
    }
}
