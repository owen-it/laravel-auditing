<?php

namespace OwenIt\Auditing;

use Illuminate\Support\Manager;
use InvalidArgumentException;
use OwenIt\Auditing\Contracts\Auditable;
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
     * {@inheritdoc}
     */
    public function audit(Auditable $model)
    {
        $auditors = $model->getAuditors();

        foreach ((array) $auditors as $auditor) {
            $model = clone $model;

            // Review audit
            if (!$this->auditReview($model, $auditor)) {
                continue;
            }

            $report = $this->driver($auditor)->audit($model);

            // Report audit
            $this->app->make('events')->fire(
                new Events\AuditReport($model, $auditor, $report)
            );
        }
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
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     * @param string                               $auditor
     *
     * @return bool
     */
    protected function auditReview(Auditable $model, $auditor)
    {
        return $this->app->make('events')->until(
            new Events\AuditReview($model, $auditor)
        ) !== false;
    }

    /**
     * {@inheritdoc}
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
     * @return \OwenIt\Auditing\Auditors\DatabaseAuditor
     */
    protected function createDatabaseDriver()
    {
        return $this->app->make(Auditors\DatabaseAuditor::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultDriver()
    {
        return $this->defaultAuditor;
    }
}
