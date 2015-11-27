<?php

namespace OwenIt\Auditing;

use Illuminate\Database\Eloquent\Model;

class Auditing extends Model
{
    use AuditingTrait;

    /**
     * @var bool
     */
    protected $auditEnabled = true;

    /**
     * @var array
     */
    protected $auditableTypes = ['created', 'saved', 'deleted'];

    /**
     * @var string
     */
    public static $logCustomMessage = '{type} in {created_at}';

    /**
     * @var array
     */
    public static $logCustomFields = [];
}
