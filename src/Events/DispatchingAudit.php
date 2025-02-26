<?php

namespace OwenIt\Auditing\Events;

use OwenIt\Auditing\Contracts\Auditable;

class DispatchingAudit
{
    /**
     * Create a new DispatchingAudit event instance.
     */
    public function __construct(
        public Auditable $model
    ) {
        //
    }
}
