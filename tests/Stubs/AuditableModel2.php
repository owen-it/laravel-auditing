<?php

namespace OwenIt\Auditing\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class AuditableModel2 extends Model implements AuditableContract
{
    use Auditable;

    public static $auditRespectsHidden = true;
}
