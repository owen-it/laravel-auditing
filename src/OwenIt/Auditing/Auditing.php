<?php

namespace OwenIt\Auditing;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Log;

class Auditing extends Model
{
	use AuditingTrait;
}
