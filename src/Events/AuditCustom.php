<?php

namespace OwenIt\Auditing\Events;

use OwenIt\Auditing\Contracts\Auditable;

class AuditCustom
{
    /**
     * Create a new Auditing event instance.
     */
    public function __construct(
        public Auditable $model
    ) {
        //
    }
}
