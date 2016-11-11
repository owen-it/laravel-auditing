<?php
namespace OwenIt\Auditing\Relations;

/**
 * Class BelongsToManyDecorator
 * Stole most everything form here: https://github.com/laravel/framework/pull/14988
 *
 * @package OwenIt\Auditing\Relations
 */
class BelongsToMany extends \Illuminate\Database\Eloquent\Relations\BelongsToMany
{
    /**
     * Attach a model to the parent.
     *
     * @param  mixed  $id
     * @param  array  $attributes
     * @param  bool   $touch
     * @return void
     */
    public function attach($id, array $attributes = [], $touch = true)
    {
        // Attach the records
        parent::attach($id, $attributes, $touch);

        // Get the attached records
        if ($id instanceof Model) {
            $id = $id->getKey();
        }

        if ($id instanceof Collection) {
            $id = $id->modelKeys();
        }

        $records = $this->createAttachRecords((array) $id, $attributes);

        // Notify the parent model
        foreach ($records as $record) {
            $this->fireParentEvent(
                'attached',
                [
                    'relationId' => $record[$this->otherKey],
                    'relationName' => $this->relationName,
                    'newData' => $record
                ],
                false
            );
        }
    }

    /**
     * Update an existing pivot record on the table.
     *
     * @param  mixed  $id
     * @param  array  $attributes
     * @param  bool   $touch
     * @return int
     */
    public function updateExistingPivot($id, array $attributes, $touch = true)
    {
        // Remember the current data
        $oldData = $this->newPivotStatementForId($id)
            ->select(array_keys($attributes))
            ->first();

        // Update the pivot
        $updated =  parent::updateExistingPivot($id, $attributes, $touch);

        // Notify the parent model
        if ($updated > 0) {
            $this->fireParentEvent(
                'updatedRelation',
                [
                    'relationId' => $id,
                    'relationName' => $this->relationName,
                    'oldData' => (array) $oldData,
                    'newData' => $attributes
                ],
                false
            );
        }

        return $updated;
    }

    /**
     * Detach models from the relationship.
     *
     * @param  mixed  $ids
     * @param  bool  $touch
     * @return int
     */
    public function detach($ids = [], $touch = true)
    {
        // Get the detachable items
        if ($ids instanceof Model) {
            $ids = $ids->getKey();
        }

        if ($ids instanceof Collection) {
            $ids = $ids->modelKeys();
        }

        $records = ($this->getRelated()->find((array)$ids));

        // Detach the related items
        parent::detach($ids, $touch);

        // Notify the parent model
        foreach ($records as $record) {
            $this->fireParentEvent(
                'detached',
                [
                    'relationId' => $record[$this->getRelated()->getKeyName()],
                    'relationName' => $this->relationName,
                    'oldData' => $record->toArray()
                ],
                false
            );
        }
    }

    /**
     * Fire the given event for the parent model.
     *
     * @param  string $event
     * @param array $records
     * @param  bool $halt
     * @return mixed
     */
    protected function fireParentEvent($event, $records, $halt = true)
    {
        $dispatcher = $this->getParent()->getEventDispatcher();

        if (!$dispatcher) {
            return true;
        }

        $event = "eloquent.{$event}: ".get_class($this->getParent());

        $method = $halt ? 'until' : 'fire';

        return $dispatcher->$method($event, [$this->getParent(), $records]);
    }

}