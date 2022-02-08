<?php

namespace OwenIt\Auditing;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use OwenIt\Auditing\Events\AuditCustom;
use OwenIt\Auditing\Listeners\RecordCustomAudit;

class AuditingEventServiceProvider extends ServiceProvider
{
    protected $listen = [
        AuditCustom::class => [
            RecordCustomAudit::class,
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
}
