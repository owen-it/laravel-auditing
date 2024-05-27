<?php

namespace OwenIt\Auditing\Events;

use OwenIt\Auditing\Contracts\Auditable;
use ReflectionClass;

class DispatchAudit
{
    /**
     * The Auditable model.
     *
     * @var Auditable
     */
    public $model;

    /**
     * Create a new DispatchAudit event instance.
     *
     * @param Auditable $model
     */
    public function __construct(Auditable $model)
    {
        $this->model = $model;
    }

    /**
     * Prepare the instance values for serialization.
     *
     * @return array
     */
    public function __serialize()
    {
        $values = [
            'class' => get_class($this->model),
            'model_data' => [
                'exists' => true,
                'connection' => $this->model->getQueueableConnection()
            ]
        ];

        $customProperties = array_merge([
            'attributes',
            'original',
            'excludedAttributes',
            'auditEvent',
            'auditExclude',
            'auditCustomOld',
            'auditCustomNew',
            'isCustomEvent',
            'preloadedResolverData',
        ], $this->model->auditEventSerializedProperties ?? []);

        $reflection = new ReflectionClass($this->model);

        foreach ($customProperties as $key) {
            try {
                $values['model_data'][$key] = $this->getModelPropertyValue($reflection, $key);
            } catch (\Throwable $e){
                //
            }
        }

        return $values;
    }

    /**
     * Restore the model after serialization.
     *
     * @param  array  $values
     * @return array
     */
    public function __unserialize(array $values)
    {
        $this->model = new $values['class'];

        $reflection = new ReflectionClass($this->model);
        foreach ($values['model_data'] as $key => $value) {
            $this->setModelPropertyValue($reflection, $key, $value);
        }

        return $values;
    }

    /**
     * Set the property value for the given property.
     */
    protected function setModelPropertyValue(ReflectionClass $reflection, string $name, $value)
    {
        $property = $reflection->getProperty($name);

        $property->setAccessible(true);

        $property->setValue($this->model, $value);
    }

    /**
     * Get the property value for the given property.
     *
     * @return mixed
     */
    protected function getModelPropertyValue(ReflectionClass $reflection, string $name)
    {
        $property = $reflection->getProperty($name);

        $property->setAccessible(true);

        return $property->getValue($this->model);
    }
}
