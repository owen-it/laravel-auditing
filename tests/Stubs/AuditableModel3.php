<?php

namespace OwenIt\Auditing\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class AuditableModel3 extends Model implements AuditableContract
{
    use Auditable;

    protected $hidden = [
        'password',
    ];

    protected $auditRespectsHidden = true;

    protected $auditableEvents = [
        'created',
    ];
}
