<?php

namespace OwenIt\Auditing;

use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Dispatcher;

trait Auditor 
{
    /**
     * @var array
     */
    protected $auditorsDefaults = ['database'];

    /**
     * Audit the given information.
     *
     * @return void
     */
    public function audit()
    {
        app(Dispatcher::class)->audit($this);
    }

    /**
     * Get the Auditors.
     *
     * @return array
     */
    public function getAuditors(){
        return isset($this->auditors) ? (array) $this->auditors : $this->auditorsDefaults;
    }
}