<?php

namespace OwenIt\Auditing\Events;

use OwenIt\Auditing\Contracts\Auditable;
use ReflectionClass;

class DispatchAudit
{
    /**
     * Create a new DispatchAudit event instance.
     */
    public function __construct(
        public Auditable $model
    ) {
        //
    }

    /**
     * Prepare the instance values for serialization.
     *
     * @return array<string,mixed>
     */
    public function __serialize()
    {
        $values = [
            'class' => get_class($this->model),
            'model_data' => [
                'exists' => true,
                'connection' => $this->model->getQueueableConnection(),
            ],
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
            } catch (\Throwable $e) {
                //
            }
        }

        return $values;
    }

    /**
     * Restore the model after serialization.
     *
     * @param  array<string,mixed>  $values
     */
    public function __unserialize(array $values): void
    {
        $model = new $values['class'];

        if (! $model instanceof Auditable) {
            return;
        }

        $this->model = $model;
        $reflection = new ReflectionClass($this->model);
        foreach ($values['model_data'] as $key => $value) {
            $this->setModelPropertyValue($reflection, $key, $value);
        }
    }

    /**
     * Set the property value for the given property.
     *
     * @param  ReflectionClass<Auditable>  $reflection
     * @param  mixed  $value
     */
    protected function setModelPropertyValue(ReflectionClass $reflection, string $name, $value): void
    {
        $property = $reflection->getProperty($name);

        $property->setAccessible(true);

        $property->setValue($this->model, $value);
    }

    /**
     * Get the property value for the given property.
     *
     * @param  ReflectionClass<Auditable>  $reflection
     * @return mixed
     */
    protected function getModelPropertyValue(ReflectionClass $reflection, string $name)
    {
        $property = $reflection->getProperty($name);

        $property->setAccessible(true);

        return $property->getValue($this->model);
    }
}
