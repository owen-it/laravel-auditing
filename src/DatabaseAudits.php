<?php

namespace OwenIt\Auditing;

trait DatabaseAudits
{
    /**
     * Idenfiable name.
     *
     * @return mixed
     */
    public function identifiableName()
    {
        return $this->getKey();
    }

    /**
     * Get the entity's audits.
     */
    public function audits()
    {
        return $this->morphMany(Auditing::class, 'auditable');
    }

    /**
     * Clear the oldest audit's if given a limit.
     *
     * @return void
     */
    public function clearOlderAudits()
    {
        $auditsHistoryCount = $this->audits()->count();

        $auditsHistoryOlder = $auditsHistoryCount - $this->auditLimit;

        if (isset($this->auditLimit) && $auditsHistoryOlder > 0) {
            $this->audits()->orderBy('created_at', 'asc')
                 ->limit($auditsHistoryOlder)->delete();
        }
    }
}
