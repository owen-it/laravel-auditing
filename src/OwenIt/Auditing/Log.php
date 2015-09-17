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
     * Added attribute
     *
     * @var array
     */
    protected $appends = ['custom_message', 'custom_fields', 'elapsed_time'];

    /**
     * Get model auditing
     *
     * @return array revision history
     */
    public function owner()
    {
        return $this->morphTo();
    }
    
    /**
     * Author responsible for the change
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(\Config::get('auth.model'));
    }

    /**
     * Returns data of model
     *
     * @return Object|false
     */
    public function restore()
    {
        if (class_exists($class = $this->owner_type)) {
            $model = $this->$class->findOrFail($this->owner_id);
            $model->fill($this->old_value);
            return $model->save();
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
     * Get elapsed time
     * 
     * @return mixed
     */
    public function getElapsedTimeAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Custom output message
     *
     * @return mixed
     */
    public function getCustomMessageAttribute()
    {
        if( class_exists($class = $this->owner_type))
            return $this->resolveCustomMessage($class::$logCustomMessage);
        else
            return false;
    }
 
    /**
     * Custom output fields
     *
     * @return array
     */
    public function getCustomFieldsAttribute()
    {
        if(class_exists($class = $this->owner_type)){
            $customFields = [];
            foreach($class::$logCustomFields as $field => $message)
                $customFields[$field] = $this->resolveCustomMessage($message);
 
            return $customFields;
        } else {
            return false;
        }
    }
    
    /**
     * Resolve custom message
     *
     * @param $message
     * @return mixed
     */
    public function resolveCustomMessage($message)
    {
        preg_match_all('/\{[\w.]+\}/', $message, $segments);
        foreach(current($segments) as $segment){
            $key = str_replace(['{', '}'], '', $segment);
            $message = str_replace($segment, $this->valueSegment($this, $key, $key), $message);
        }
 
        return $message;
    }
    
    /**
     * Get Value of segment
     *
     * @param $object
     * @param $key
     * @param $default
     * @return mixed
     */
    public function valueSegment($object, $key, $default)
    {
        if (is_null($key) || trim($key) == '') {
            return $object;
        }

        foreach (explode('.', $key) as $segment) 
        {
            $object = is_object($object) ? $object : (object) $object;
            if (! isset($object->{$segment}) ) {
                return $default;
            }

            $object = $object->{$segment};
        }

        return $object;
    }

}
