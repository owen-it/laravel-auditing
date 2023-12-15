<?php

namespace OwenIt\Auditing\Concerns;

use Illuminate\Support\Facades\Event;
use OwenIt\Auditing\Events\AuditCustom;
use OwenIt\Auditing\Exceptions\AuditingException;

trait AuditsPivotRecords
{
    /*
    |--------------------------------------------------------------------------
    | Pivot help methods
    |--------------------------------------------------------------------------
    |
    | Methods for auditing pivot actions
    |
    */

    /**
     * @param string $relationName
     * @param mixed $id
     * @param array $attributes
     * @param bool $touch
     * @param array $columns
     * @return void
     * @throws AuditingException
     */
    public function auditAttach(string $relationName, $id, array $attributes = [], $touch = true, $columns = ['*'])
    {
        if (!method_exists($this, $relationName) || !method_exists($this->{$relationName}(), 'attach')) {
            throw new AuditingException('Relationship ' . $relationName . ' was not found or does not support method attach');
        }

        $old = $this->{$relationName}()->get($columns);
        $this->{$relationName}()->attach($id, $attributes, $touch);
        $new = $this->{$relationName}()->get($columns);
        $this->dispatchRelationAuditEvent($relationName, 'attach', $old, $new);
    }

    /**
     * @param string $relationName
     * @param mixed $ids
     * @param bool $touch
     * @param array $columns
     * @return int
     * @throws AuditingException
     */
    public function auditDetach(string $relationName, $ids = null, $touch = true, $columns = ['*'])
    {
        if (!method_exists($this, $relationName) || !method_exists($this->{$relationName}(), 'detach')) {
            throw new AuditingException('Relationship ' . $relationName . ' was not found or does not support method detach');
        }

        $old = $this->{$relationName}()->get($columns);
        $results = $this->{$relationName}()->detach($ids, $touch);
        $new = $this->{$relationName}()->get($columns);
        $this->dispatchRelationAuditEvent($relationName, 'detach', $old, $new);

        return empty($results) ? 0 : $results;
    }

    /**
     * @param $relationName
     * @param \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Model|array $ids
     * @param bool $detaching
     * @param array $columns
     * @return array
     * @throws AuditingException
     */
    public function auditSync($relationName, $ids, $detaching = true, $columns = ['*'])
    {
        if (!method_exists($this, $relationName) || !method_exists($this->{$relationName}(), 'sync')) {
            throw new AuditingException('Relationship ' . $relationName . ' was not found or does not support method sync');
        }

        $old = $this->{$relationName}()->get($columns);
        $changes = $this->{$relationName}()->sync($ids, $detaching);
        if (collect($changes)->flatten()->isEmpty()) {
            $old = $new = collect([]);
        } else {
            $new = $this->{$relationName}()->get($columns);
        }
        $this->dispatchRelationAuditEvent($relationName, 'sync', $old, $new);

        return $changes;
    }

    /**
     * @param string $relationName
     * @param \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Model|array $ids
     * @param array $columns
     * @return array
     * @throws AuditingException
     */
    public function auditSyncWithoutDetaching(string $relationName, $ids, $columns = ['*'])
    {
        if (!method_exists($this, $relationName) || !method_exists($this->{$relationName}(), 'syncWithoutDetaching')) {
            throw new AuditingException('Relationship ' . $relationName . ' was not found or does not support method syncWithoutDetaching');
        }

        return $this->auditSync($relationName, $ids, false, $columns);
    }

    /**
     * @param string $relationName
     * @param string $event
     * @param \Illuminate\Support\Collection $old
     * @param \Illuminate\Support\Collection $new
     * @return void
     */
    private function dispatchRelationAuditEvent($relationName, $event, $old, $new)
    {
        $this->auditCustomOld[$relationName] = $old->diff($new)->toArray();
        $this->auditCustomNew[$relationName] = $new->diff($old)->toArray();

        if (
            empty($this->auditCustomOld[$relationName]) &&
            empty($this->auditCustomNew[$relationName])
        ) {
            $this->auditCustomOld = $this->auditCustomNew = [];
        }

        $this->auditEvent = $event;
        $this->isCustomEvent = true;
        Event::dispatch(AuditCustom::class, [$this]);
        $this->isCustomEvent = false;
    }
}
