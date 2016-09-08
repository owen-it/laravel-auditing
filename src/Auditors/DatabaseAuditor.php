<?php

namespace OwenIt\Auditing\Auditors;

use RuntimeException;
use OwenIt\Auditing\Auditing;

class DatabaseAuditor 
{
    /**
     * Audit the given model.
     *
     * @param  mixed  $auditable
     * @return \Illuminate\Database\Eloquent\Model
     */
	public function audit($auditable)
	{
		$report = Auditing::create(
            $auditable->toAudit()
        );

        if($report){
            $auditable->clearOlderAudits();
        }

        return $report;
	}
    
}