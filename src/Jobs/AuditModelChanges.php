<?php

namespace OwenIt\Auditing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\AuditDriver;
use OwenIt\Auditing\Events\Audited;
use Throwable;

class AuditModelChanges implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    public $model;
    public $auditDriver;
    public $event;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(AuditDriver $auditDriver, Auditable $model, $event)
    {
        $this->auditDriver = $auditDriver;
        $this->model = $model;
        $this->event = $event;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->model->setAuditEvent($this->event);
        $audit = $this->auditDriver->audit($this->model);
        if (!$audit) {
            $this->fail();
        }
        App::make('events')->dispatch(
            new Audited($this->model, $this->auditDriver, $audit)
        );
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        Log::info('error in auditing data with job' . $exception->getMessage() . " error code: " . $exception->getCode());
    }
}
