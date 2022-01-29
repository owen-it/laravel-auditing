<?php

namespace OwenIt\Auditing\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

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
        'old_values'   => 'json',
        'new_values'   => 'json',
        // Note: Please do not add 'auditable_id' in here, as it will break non-integer PK models
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
