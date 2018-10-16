<?php

namespace OwenIt\Auditing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Audit extends Model implements \OwenIt\Auditing\Contracts\Audit
{
    use \OwenIt\Auditing\Audit;

    /**
     * {@inheritdoc}
     */
    protected $guarded = [];

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'old_values'    => 'json',
        'new_values'    => 'json',
        'auditable_id'  => 'integer',
    ];

    /**
     * {@inheritdoc}
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * {@inheritdoc}
     */
    public function user(): MorphTo
    {
        return $this->morphTo();
    }
}
