<?php

namespace OwenIt\Auditing\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class AuditableModelStub extends Model implements AuditableContract
{
    use Auditable;

    /**
     * {@inheritdoc}
     */
    public function resolveIpAddress()
    {
        return '127.0.0.1';
    }
}
