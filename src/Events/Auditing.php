<?php

namespace OwenIt\Auditing\Events;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\AuditDriver;

class Auditing
{
    /**
     * Create a new Auditing event instance.
     */
    public function __construct(
        public Auditable $model,
        public AuditDriver $driver
    ) {
        //
    }
}
