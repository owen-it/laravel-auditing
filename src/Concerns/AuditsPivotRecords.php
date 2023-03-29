<?php

namespace OwenIt\Auditing\Concerns;

use Illuminate\Support\Facades\Event;
use OwenIt\Auditing\Events\AuditCustom;
use OwenIt\Auditing\Exceptions\AuditingException;

trait AuditsPivotRecords
{
    /**
     * @param  mixed  $id
     * @param  bool  $touch
     * @param  string[]  $columns
     *
     * @throws AuditingException
     */
    public function auditAttach(string $relationName, $id, array $attributes = [], $touch = true, $columns = ['name'])
    {
        if (! method_exists($this, $relationName) || ! method_exists($this->{$relationName}(), 'attach')) {
            throw new AuditingException('Relationship '.$relationName.' was not found or does not support method attach');
        }
        $this->auditEvent = 'attach';
        $this->isCustomEvent = true;
        $this->auditCustomOld = [
            $relationName => $this->{$relationName}()->get()->isEmpty() ? [] : $this->{$relationName}()->get()->toArray(),
        ];
        $this->{$relationName}()->attach($id, $attributes, $touch);
        $this->auditCustomNew = [
            $relationName => $this->{$relationName}()->get()->isEmpty() ? [] : $this->{$relationName}()->get()->toArray(),
        ];
        Event::dispatch(AuditCustom::class, [$this]);
        $this->isCustomEvent = false;
    }

    /**
     * @param  mixed  $ids
     * @param  bool  $touch
     * @return int
     *
     * @throws AuditingException
     */
    public function auditDetach(string $relationName, $ids = null, $touch = true)
    {
        if (! method_exists($this, $relationName) || ! method_exists($this->{$relationName}(), 'detach')) {
            throw new AuditingException('Relationship '.$relationName.' was not found or does not support method detach');
        }

        $this->auditEvent = 'detach';
        $this->isCustomEvent = true;
        $this->auditCustomOld = [
            $relationName => $this->{$relationName}()->get()->isEmpty() ? [] : $this->{$relationName}()->get()->toArray(),
        ];
        $results = $this->{$relationName}()->detach($ids, $touch);
        $this->auditCustomNew = [
            $relationName => $this->{$relationName}()->get()->isEmpty() ? [] : $this->{$relationName}()->get()->toArray(),
        ];
        Event::dispatch(AuditCustom::class, [$this]);
        $this->isCustomEvent = false;

        return empty($results) ? 0 : $results;
    }

    /**
     * @param  \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Model|array  $ids
     * @param  bool  $detaching
     * @param  bool  $skipUnchanged
     * @return array
     *
     * @throws AuditingException
     */
    public function auditSync($relationName, $ids, $detaching = true)
    {
        if (! method_exists($this, $relationName) || ! method_exists($this->{$relationName}(), 'sync')) {
            throw new AuditingException('Relationship '.$relationName.' was not found or does not support method sync');
        }

        $this->auditEvent = 'sync';

        $this->auditCustomOld = [
            $relationName => $this->{$relationName}()->get()->isEmpty() ? [] : $this->{$relationName}()->get()->toArray(),
        ];

        $changes = $this->{$relationName}()->sync($ids, $detaching);

        if (collect($changes)->flatten()->isEmpty()) {
            $this->auditCustomOld = [];
            $this->auditCustomNew = [];
        } else {
            $this->auditCustomNew = [
                $relationName => $this->{$relationName}()->get()->isEmpty() ? [] : $this->{$relationName}()->get()->toArray(),
            ];
        }

        $this->isCustomEvent = true;
        Event::dispatch(AuditCustom::class, [$this]);
        $this->isCustomEvent = false;

        return $changes;
    }

    /**
     * @param  \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Model|array  $ids
     * @param  bool  $skipUnchanged
     * @return array
     *
     * @throws AuditingException
     */
    public function auditSyncWithoutDetaching(string $relationName, $ids)
    {
        if (! method_exists($this, $relationName) || ! method_exists($this->{$relationName}(), 'syncWithoutDetaching')) {
            throw new AuditingException('Relationship '.$relationName.' was not found or does not support method syncWithoutDetaching');
        }

        return $this->auditSync($relationName, $ids, false);
    }
}
