<?php
/**
 * This file is part of the Laravel Auditing package.
 *
 * @author     Antério Vieira <anteriovieira@gmail.com>
 * @author     Quetzy Garcia  <quetzyg@altek.org>
 * @author     Raphael França <raphaelfrancabsb@gmail.com>
 * @copyright  2015-2017
 *
 * For the full copyright and license information,
 * please view the LICENSE.md file that was distributed
 * with this source code.
 */

namespace OwenIt\Auditing;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Manager;
use InvalidArgumentException;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Contracts\AuditDriver;
use OwenIt\Auditing\Contracts\Auditor as AuditorContract;
use OwenIt\Auditing\Drivers\Database;
use OwenIt\Auditing\Events\Audited;
use OwenIt\Auditing\Events\Auditing;
use RuntimeException;

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
    public function auditDriver(AuditableContract $model)
    {
        $driver = $this->driver($model->getAuditDriver());

        if (!$driver instanceof AuditDriver) {
            throw new RuntimeException('The driver must implement the AuditDriver contract');
        }

        return $driver;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(AuditableContract $model)
    {
        if (!$model->readyForAuditing()) {
            return;
        }

        $driver = $this->auditDriver($model);

        if (!$this->fireAuditingEvent($model, $driver)) {
            return;
        }

        if ($audit = $driver->audit($model, null)) {
            $driver->prune($model);
        }

        if ($model->getAuditRelatedProperties()) {
            $audit->relation_id = $audit->id;
            $audit->save();
        }
        foreach ($model->getAuditRelatedProperties() as $methodOrPropertyName) {
            if (property_exists($model, $methodOrPropertyName)) {
                $methodResult = $model->$methodOrPropertyName;
            } elseif (method_exists($model, $methodOrPropertyName)) {
                $methodResult = $model->$methodOrPropertyName();
            } else {
                throw new RuntimeException('Related audit failed. Check model (of class '.get_class($model).') and ensure that method or property '.$methodOrPropertyName.' is defined');
            }
            if ($methodResult instanceof Relation) {
                $methodResult = $methodResult->get();
            }
            if ($methodResult instanceof Collection) {
                foreach ($methodResult as $relatedModel) {
                    if (!$relatedAudit = $driver->audit($relatedModel, $audit->id)) {
                        throw new RuntimeException(
                            'Related audit failed. Check model and ensure that class '.get_class($relatedModel).
                            ' (which is related to  '.get_class($model).') has the auditable trait.'
                        );
                    }
                }
            } elseif ($methodResult) {
                if (!$relatedAudit = $driver->audit($methodResult, $audit->id)) {
                    throw new RuntimeException(
                        'Related audit failed. Check model and ensure that class '.get_class($methodResult).
                        ' (which is related to  '.get_class($model).') has the auditable trait.'
                    );
                }
            } else {
                throw new RuntimeException(
                    'Related audit failed. Check model and ensure that class '.get_class($model).
                    ' does not have a method named '.$methodOrPropertyName.'. See model->auditRelatedProperties'
                );
            }
        }

        $this->app->make('events')->fire(
            new Audited($model, $driver, $audit)
        );
    }

    /**
     * Create an instance of the Database audit driver.
     *
     * @return \OwenIt\Auditing\Drivers\Database
     */
    protected function createDatabaseDriver()
    {
        return $this->app->make(Database::class);
    }

    /**
     * Fire the Auditing event.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable   $model
     * @param \OwenIt\Auditing\Contracts\AuditDriver $driver
     *
     * @return bool
     */
    protected function fireAuditingEvent(AuditableContract $model, AuditDriver $driver)
    {
        return $this->app->make('events')->until(
            new Auditing($model, $driver)
        ) !== false;
    }
}
