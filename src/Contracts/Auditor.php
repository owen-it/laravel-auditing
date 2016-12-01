<?php

namespace OwenIt\Auditing\Contracts;

interface Auditor
{
    /**
     * Perform an audit.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function audit(Auditable $model);
}
