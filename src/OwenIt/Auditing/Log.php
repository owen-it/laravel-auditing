<?php

namespace OwenIt\Auditing;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    public $table = 'logs';

    /**
     * Auditing.
     *
     * Grab the revision history for the model that is calling
     *
     * @return array revision history
     */
    public function auditing()
    {
        return $this->morphTo();
    }

    /**
     * Returns the object we have the history of
     *
     * @return Object|false
     */
    public function historyOf()
    {
        if (class_exists($class = $this->owner_type)) {
            return $class::find($this->owner_id);
        }
        return false;
    }
}
