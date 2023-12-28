<?php

namespace OwenIt\Auditing\Events;

use Illuminate\Queue\SerializesModels;
use OwenIt\Auditing\Contracts\Auditable;
use ReflectionClass;

class DispatchAudit
{
    use SerializesModels {
        __serialize as __serialize_model;
        __unserialize as __unserialize_model;
    }

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
        $values = $this->__serialize_model();

        $values['model_data'] = ['exists' => true];
        $reflection = new ReflectionClass($this->model);
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

        foreach ($customProperties as $key) {
            try {
                $values['model_data'][$key] = $this->getSerializedPropertyValue(
                    $this->getModelPropertyValue($reflection, $key)
                );
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
        $this->__unserialize_model($values);

        $reflection = new ReflectionClass($this->model);
        foreach ($values['model_data'] as $key => $value) {
            $this->setModelPropertyValue($reflection, $key, $value);
        }

        return $values;
    }

    /**
     * Restore the model from the model identifier instance.
     *
     * @param  \Illuminate\Contracts\Database\ModelIdentifier  $value
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function restoreModel($value)
    {
        return (new $value->class)->setConnection($value->connection);
    }

    /**
     * Set the property value for the given property.
     */
    protected function setModelPropertyValue(ReflectionClass $reflection, string $name, $value)
    {
        $property = $reflection->getProperty($name);

        $property->setAccessible(true);

        $property->setValue($this->model, $this->getRestoredPropertyValue($value));
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
