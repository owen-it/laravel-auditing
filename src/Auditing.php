<?php

namespace OwenIt\Auditing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class Auditing extends Model
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'audits';

    /**
     * The guarded attributes on the model.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'old' => 'json',
        'new' => 'json',
    ];

    /**
     * The attributes that should be appends.
     *
     * @var array
     */
    protected $appends = [
        'custom_message',
        'custom_fields',
        'elapsed_time',
    ];

    /**
     * Get the auditable entity that the audits belongs to.
     */
    public function auditable()
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
     * Get elapsed time.
     *
     * @return mixed
     */
    public function getElapsedTimeAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Custom output message.
     *
     * @return mixed
     */
    public function getCustomMessageAttribute()
    {
        if (class_exists($class = $this->auditable_type)) {
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
        if (class_exists($class = $this->auditable_type)) {
            $customFields = [];

            foreach ($this->getCustomFields($class) as $field => $message) {
                if (is_array($message) && isset($message[$this->type])) {
                    $customFields[$field] = $this->resolveCustomMessage($message[$this->type]);
                } elseif (is_string($message)) {
                    $customFields[$field] = $this->resolveCustomMessage($message);
                }
            }

            return array_filter($customFields);
        }

        return [];
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
        $auditable = $this->auditable();

        if (method_exists($auditable, $method)) {
            return $auditable->{$method}($this);
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
        if (empty($key)) {
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

        if ($table) {
            return $table;
        }

        return str_replace('\\', '', Str::snake(Str::plural(class_basename($this))));
    }
}
