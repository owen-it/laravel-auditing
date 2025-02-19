<?php

namespace OwenIt\Auditing\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;
use OwenIt\Auditing\Events\DispatchAudit;
use OwenIt\Auditing\Facades\Auditor;

class ProcessDispatchAudit implements ShouldQueue
{
    public function viaConnection(): string
    {
        return Config::get('audit.queue.connection', 'sync');
    }

    public function viaQueue(): string
    {
        return Config::get('audit.queue.queue', 'default');
    }

    public function withDelay(DispatchAudit $event): int
    {
        return Config::get('audit.queue.delay', 0);
    }

    public function handle(DispatchAudit $event): void
    {
        Auditor::execute($event->model);
    }
}
