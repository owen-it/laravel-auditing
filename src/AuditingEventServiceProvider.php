<?php

namespace OwenIt\Auditing;

if (app() instanceof \Illuminate\Foundation\Application) {
    class_alias(\Illuminate\Foundation\Support\Providers\EventServiceProvider::class, '\OwenIt\Auditing\ServiceProvider');
} else {
    class_alias(\Laravel\Lumen\Providers\EventServiceProvider::class, '\OwenIt\Auditing\ServiceProvider');
}

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Config;
use OwenIt\Auditing\Events\AuditCustom;
use OwenIt\Auditing\Events\DispatchAudit;
use OwenIt\Auditing\Listeners\RecordCustomAudit;
use OwenIt\Auditing\Listeners\ProcessDispatchAudit;

class AuditingEventServiceProvider extends ServiceProvider
{
    protected $listen = [
        AuditCustom::class => [
            RecordCustomAudit::class,
        ],
        DispatchAudit::class => [
            ProcessDispatchAudit::class,
        ],
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
