<?php

namespace OwenIt\Auditing;

use Illuminate\Contracts\Bus\Dispatcher as Bus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Manager;
use InvalidArgumentException;
use OwenIt\Auditing\Contracts\Dispatcher;

class AuditorManager extends Manager implements Dispatcher
{
    /**
     * The default auditor used to audit model.
     *
     * @var string
     */
    protected $defaultAuditor = 'database';

    /**
     * Audit the given information.
     *
     * @param $auditable
     *
     * @return void
     */
    public function audit($auditable)
    {
        if ($auditable instanceof ShouldQueue && Config::get('auditing.should_queue', false)) {
            return $this->queueAudit($auditable);
        }

        $auditors = $auditable->getAuditors();

        if (empty($auditors)) {
            return;
        }

        foreach ((array) $auditors as $auditor) {
            $auditable = clone $auditable;

            // Review audit
            if (!$this->auditReview($auditable, $auditor)) {
                continue;
            }

            $report = $this->driver($auditor)->audit($auditable);

            // Report audit
            $this->app->make('events')->fire(
                new Events\AuditReport($auditable, $auditor, $report)
            );
        }
    }

    /**
     * Queue the given auditable instance.
     *
     * @param mixed $auditable
     *
     * @return void
     */
    public function queueAudit($auditable)
    {
        $bus = $this->app->make(Bus::class);

        $job = (new AuditQueueModels($auditable));

        $bus->dispatcher($job);
    }

    /**
     * Get a auditor instance.
     *
     * @param string|null $name
     *
     * @return mixed
     */
    public function auditor($name = null)
    {
        return $this->driver($name);
    }

    /**
     * Review audit and determines if the
     * entity can be audited.
     *
     * @param mixed  $auditable
     * @param string $auditor
     *
     * @return bool
     */
    protected function auditReview($auditable, $auditor)
    {
        return $this->app->make('events')->until(
            new Events\AuditReview($auditable, $auditor)
        ) !== false;
    }

    /**
     * Create a new driver instance.
     *
     * @param string $driver
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed
     */
    protected function createDriver($driver)
    {
        try {
            return parent::createDriver($driver);
        } catch (InvalidArgumentException $e) {
            if (class_exists($driver)) {
                return $this->app->make($driver);
            }

            throw $e;
        }
    }

    /**
     * Create an instance of the database driver.
     *
     * @return \OwenIt\Auditing\Auditor\DatabaseAuditor
     */
    protected function createDatabaseDriver()
    {
        return $this->app->make(Auditors\DatabaseAuditor::class);
    }

    /**
     * Get the default auditor driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->defaultAuditor;
    }
}
