<?php

namespace OwenIt\Auditing\Tests\Models;

use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as ContractsAuditable;

class GroupMember extends \Illuminate\Database\Eloquent\Model implements ContractsAuditable
{
    use AsPivot;
    use Auditable;
    
    public $fillable = [
        "role"
    ];
}
