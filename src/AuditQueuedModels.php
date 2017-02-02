<?php

namespace OwenIt\Auditing;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class AuditQueuedModels implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

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
     * Audit the model auditable.
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
