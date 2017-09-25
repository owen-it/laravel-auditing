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
use Illuminate\Support\Manager;
use InvalidArgumentException;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Contracts\AuditDriver;
use OwenIt\Auditing\Contracts\Auditor as AuditorContract;
use OwenIt\Auditing\Drivers\Database;
use OwenIt\Auditing\Events\Audited;
use OwenIt\Auditing\Events\Auditing;
use RuntimeException;
use Webpatser\Uuid\Uuid;

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

        $uuid = null;
        $list_of_properties = [];
        foreach (config('audit.relation_hierarchy', []) as $class_type_to_audit => $local_list_of_methods )
        {
            foreach ($local_list_of_methods as $local_method)
            {
                if ($model instanceof $class_type_to_audit)
                {
                    $list_of_properties[] = $local_method['property'];
                    $uuid = (string) Uuid::generate();

                }
                elseif ($model instanceof $class_type_to_audit)
                {
                    if ($model instanceof $local_method['yields'])
                    {
                        $list_of_properties[] = $local_method['relator'];
                        $uuid = (string) Uuid::generate();
                    }
                }
            }
        }

        if ($audit = $driver->audit($model, $uuid, false)) {
            $driver->prune($model);
        }

        if ($uuid)
        {
            foreach ($list_of_properties as $method_name)
            {
                $method_result = $model->$method_name;
                if ($method_result instanceof Collection)
                {
                    foreach ($method_result as $related_model)
                    {
                        if ( ! $related_audit = $driver->audit($related_model, $uuid, true))
                        {
                            throw new RuntimeException(
                                'related audit failed. Check config and ensure that class ' . get_class($related_model) .
                                ' (which is related to  ' . get_class($model) . ') has the auditable trait.'
                            );
                        }
                    }
                }
                elseif ($method_result)
                {
                    if ( ! $related_audit = $driver->audit($method_result, $uuid, true))
                    {
                        throw new RuntimeException(
                            'related audit failed. Check config and ensure that class ' . get_class($x) .
                            ' (which is related to  ' . get_class($model) . ') has the auditable trait.'
                        );
                    }
                }
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

    /**
     * @param null $relation_hierarchy_arr
     * @return array
     */
    protected function get_inverted_relation_hierarchy($relation_hierarchy_arr = null)
    {
        if($relation_hierarchy_arr === null)
        {
            // feed me
            return [];
        }
    }

}
