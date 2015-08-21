<?php

namespace OwenIt\Auditing;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    /**
     * @var string
     */
    public $table = 'logs';

    /**
     * Cast values
     * @var array
     */
    protected $casts = ['old_value' => 'json', 'new_value' => 'json'];

    /**
     *
     * Get all models auditing
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
    public function owner()
    {
        if (class_exists($class = $this->owner_type)) {
            return $class::find($this->owner_id);
        }
        return false;
    }

    /**
     * Get old value
     * @return mixed
     */
    public function getOldAttribute()
    {
        return $this->old_value;
    }

    /**
     * Get new value
     * @return mixed
     */
    public function getNewAttribute()
    {
        return $this->new_value;
    }

    /**
     * Get Owner of log
     *
     * @return false|Object
     */
    public function getOwnerAttribute()
    {
        return $this->owner();
    }
}
