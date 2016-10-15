<?php

namespace OwenIt\Auditing;

use Illuminate\Support\Facades\Config;

trait CustomAuditMessage
{
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
        if (!isset($class::$auditCustomMessage)) {
            return 'Not defined custom message!';
        }

        return $class::$auditCustomMessage;
    }

    /**
     * Get custom fields.
     *
     * @return string
     */
    public function getCustomFields($class)
    {
        if (!isset($class::$auditCustomFields)) {
            return [];
        }

        return $class::$auditCustomFields;
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
            $pipe = str_replace(['{', '}'], '', $segment);

            list($property, $defaultValue, $method) = array_pad(
                explode('|', $pipe, 3), 3, null
            );

            if (empty($defaultValue) && !empty($method)) {
                $defaultValue = $this->callback($method);
            }

            $valueSegmented = $this->getValueSegmented($this, $property, $defaultValue ?: ' ');

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
        if (method_exists($this->auditable, $method)) {
            return $this->auditable->{$method}($this);
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

        if (!empty($table)) {
            return $table;
        }

        return parent::getTable();
    }
}
