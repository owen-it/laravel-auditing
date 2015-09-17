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
     * Returns the object we have the history of
     *
     * @return false|Object
     */
    public function getHistoryOfAttribute()
    {
        return $this->historyOf();
    }

    /**
     * Custom output message
     *
     * @return mixed
     */
    public function getCustomMessageAttribute()
    {
        $attributes = $this->attributes;
        $patterns = [];
        $replacements = [];
        foreach($attributes as $field => $value)
        {
            $patterns[]     = "{{$field}}";
            $replacements[] = "{$value}";
        }

        $message = class_exists($class = $this->owner_type) ?
            $class::$customMessage : 'Model not found';

        return str_replace(
            $patterns,
            $replacements,
            $message
        );
    }

    /**
     * Custom output fields
     *
     * @return array
     */
    public function getCustomFieldsAttribute()
    {
        $newcustomFields = [];
        $customFields    = class_exists($class = $this->owner_type) ?
            $class::$customFields : [];

        foreach($customFields as $field => $message)
        {
            $newcustomFields[$field] = str_replace(
                ['{new}', '{old}'],
                [$this->new_value[$field], $this->old_value[$field]],
                $message
            );
        }
        return $newcustomFields;
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
            $message = str_replace($segment, object_get($this, $key, $key), $message);
        }
 
        return $message;
    }

}
