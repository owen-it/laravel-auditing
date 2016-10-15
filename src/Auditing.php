<?php

namespace OwenIt\Auditing;

use Illuminate\Database\Eloquent\Model;

class Auditing extends Model
{
    use CustomAuditMessage;

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
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'audits';
}
