<?php

namespace OwenIt\Auditing\Contracts;

interface Dispatcher
{
    /**
     * Perform an audit to the Auditable model
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function audit(Auditable $model);
}
