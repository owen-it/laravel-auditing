<?php

namespace OwenIt\Auditing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class Audit extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'audits';

    /**
     * {@inheritdoc}
     */
    public $incrementing = false;

    /**
     * {@inheritdoc}
     */
    public $timestamps = false;

    /**
     * {@inheritdoc}
     */
    protected $guarded = [];

    /**
     * {@inheritdoc}
     */
    protected $dates = [
        'created_at',
    ];

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'old' => 'json',
        'new' => 'json',
    ];

    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
        return static::resolveConnection(Config::get('auditing.connection'));
    }

    /**
     * {@inheritdoc}
     */
    public function getTable()
    {
        return Config::get('auditing.table', parent::getTable());
    }

    /**
     * Get the auditable model to which this Audit belongs.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * User responsible for the changes.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(Config::get('auditing.model'));
    }
}
