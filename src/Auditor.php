<?php

namespace OwenIt\Auditing;

use Illuminate\Support\Manager;
use InvalidArgumentException;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\Auditor as AuditorContract;
use OwenIt\Auditing\Drivers\Database;

class Auditor extends Manager implements AuditorContract
{
    /**
     * {@inheritdoc}
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['audit.default'];
    }

    /**
     * {@inheritdoc}
     */
    protected function createDriver($driver)
    {
        try {
            return parent::createDriver($driver);
        } catch (InvalidArgumentException $exception) {
            if (class_exists($driver)) {
                return $this->app->make($driver);
            }

            throw $exception;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function audit(Auditable $model)
    {
        $drivers = $model->getAuditDrivers();

        foreach ((array) $drivers as $driver) {
            $model = clone $model;

            // Review audit
            if (!$this->auditReview($model, $driver)) {
                continue;
            }

            $report = $this->driver($driver)->audit($model);

            // Report audit
            $this->app->make('events')->fire(
                new Events\AuditReport($model, $driver, $report)
            );
        }
    }

    /**
     * Create an instance of the Database auditing driver.
     *
     * @return \OwenIt\Auditing\Drivers\Database
     */
    protected function createDatabaseDriver()
    {
        return $this->app->make(Database::class);
    }

    /**
     * Review audit and determine if the entity can be audited.
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
}
