<?php

namespace OwenIt\Auditing\Events;

class AuditReview
{
    /**
     * The auditable entity.
     *
     * @var mixed
     */
    public $auditable;

    /**
     * The auditor name.
     *
     * @var string
     */
    public $auditor;

    /**
     * Create a new event instance.
     *
     * @param mixed  $auditable
     * @param string $auditor
     *
     * @return void
     */
    public function __construct($auditable, $auditor)
    {
        $this->auditor = $auditor;

        $this->auditable = $auditable;
    }
}
