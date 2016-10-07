<?php

namespace OwenIt\Auditing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class AuditQueuedModels implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @var
     */
    private $auditable;

    /**
     * Create a new job instance.
     *
     * @param mixed $auditable
     *
     * @return void
     */
    public function __construct($auditable)
    {
        $this->auditable = $auditable;
    }

    /**
     * Audit the model.
     *
     * @param \OwenIt\Auditing\AuditorManager $manager
     *
     * @return void
     */
    public function handle(AuditorManager $manager)
    {
        $manager->audit($this->auditable);
    }
}
