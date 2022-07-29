<?php

namespace OwenIt\Auditing;

if (app() instanceof \Illuminate\Foundation\Application) {
    class_alias(\Illuminate\Foundation\Support\Providers\EventServiceProvider::class, '\OwenIt\Auditing\ServiceProvider');
} else {
    class_alias(\Laravel\Lumen\Providers\EventServiceProvider::class, '\OwenIt\Auditing\ServiceProvider');
}
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
