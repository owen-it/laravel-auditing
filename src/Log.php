<?php

namespace OwenIt\Auditing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class Log extends Model
{
    /**
     * Cast values.
     *
     * @var array
     */
    protected $casts = ['old_value' => 'json', 'new_value' => 'json'];

    /**
     * Added attribute.
     *
     * @var array
     */
    protected $appends = ['custom_message', 'custom_fields', 'elapsed_time'];

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = ['user', 'owner'];

    /**
     * Get model auditing.
     *
     * @return array revision history
     */
    public function owner()
    {
        return $this->morphTo();
    }

    /**
     * Author responsible for the change.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(Config::get('auditing.model'));
    }

    /**
     * Returns data of model.
     *
     * @return object|false
     */
    public function restore()
    {
        if (class_exists($class = $this->getActualClassNameForMorph($this->owner_type))) {
            $model = $this->$class->findOrFail($this->owner_id);

            $model->fill($this->old_value);

            return $model->save();
        }

        return false;
    }

    /**
     * Get old value.
     *
     * @return mixed
     */
    public function getOldAttribute()
    {
        return $this->old_value;
    }

    /**
     * Get new value.
     *
     * @return mixed
     */
    public function getNewAttribute()
    {
        return $this->new_value;
    }

    /**
     * Get elapsed time.
     *
     * @return mixed
     */
    public function getElapsedTimeAttribute()
    {
        return !$this->created_at ?: $this->created_at->diffForHumans();
    }

    /**
     * Custom output message.
     *
     * @return mixed
     */
    public function getCustomMessageAttribute()
    {
        if (class_exists($class = $this->getActualClassNameForMorph($this->owner_type))) {
            return $this->resolveCustomMessage($this->getCustomMessage($class));
        } else {
            return false;
        }
    }

    /**
     * Custom output fields.
     *
     * @return array
     */
    public function getCustomFieldsAttribute()
    {
        if (class_exists($class = $this->getActualClassNameForMorph($this->owner_type))) {
            $customFields = [];

            foreach ($this->getCustomFields($class) as $field => $message) {
                if (is_array($message) && isset($message[$this->type])) {
                    $customFields[$field] = $this->resolveCustomMessage($message[$this->type]);
                } elseif (is_string($message)) {
                    $customFields[$field] = $this->resolveCustomMessage($message);
                }
            }

            return array_filter($customFields);
        } else {
            return false;
        }
    }

    /**
     * Get custom message.
     *
     * @return string
     */
    public function getCustomMessage($class)
    {
        if (!isset($class::$logCustomMessage)) {
            return 'Not defined custom message!';
        }

        return $class::$logCustomMessage;
    }

    /**
     * Get custom fields.
     *
     * @return string
     */
    public function getCustomFields($class)
    {
        if (!isset($class::$logCustomFields)) {
            return [];
        }

        return $class::$logCustomFields;
    }

    /**
     * Resolve custom message.
     *
     * @param $message
     *
     * @return mixed
     */
    public function resolveCustomMessage($message)
    {
        preg_match_all('/\{[\w.| ]+\}/', $message, $segments);
        foreach (current($segments) as $segment) {
            $s = str_replace(['{', '}'], '', $segment);

            $keys = explode('|', $s);

            if (empty($keys[1]) && isset($keys[2])) {
                $keys[1] = $this->callback($keys[2]);
            }

            $valueSegmented = $this->getValueSegmented($this, $keys[0], isset($keys[1]) ? $keys[1] : ' ');

            $message = str_replace($segment, $valueSegmented, $message);
        }

        return $message;
    }

    /**
     * Message callback.
     *
     * @param $function
     *
     * @return mixed
     */
    public function callback($method)
    {
        if (method_exists($this->owner, $method)) {
            return $this->owner->{$method}($this);
        }
    }

    /**
     * Get the database connection for the model.
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return static::resolveConnection(Config::get('auditing.connection'));
    }

    /**
     * Get Value of segment.
     *
     * @param $object
     * @param $key
     * @param $default
     *
     * @return mixed
     */
    public function getValueSegmented($object, $key, $default)
    {
        if (is_null($key) || trim($key) == '') {
            return $default;
        }

        foreach (explode('.', $key) as $segment) {
            $object = is_array($object) ? (object) $object : $object;

            if (!isset($object->{$segment})) {
                return $default;
            }

            $object = $object->{$segment};
        }

        return $object;
    }

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        $table = Config::get('auditing.table');

        if (isset($table)) {
            return $table;
        }

        return str_replace('\\', '', Str::snake(Str::plural(class_basename($this))));
    }
}
