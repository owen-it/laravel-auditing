<?php

namespace OwenIt\Auditing;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use OwenIt\Auditing\Console\AuditDriverCommand;
use OwenIt\Auditing\Console\AuditResolverCommand;
use OwenIt\Auditing\Console\InstallCommand;
use OwenIt\Auditing\Contracts\Auditor;
use OwenIt\Auditing\Events\AuditCustom;
use OwenIt\Auditing\Events\DispatchAudit;
use OwenIt\Auditing\Listeners\ProcessDispatchAudit;
use OwenIt\Auditing\Listeners\RecordCustomAudit;

class AuditingServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPublishing();
        $this->mergeConfigFrom(__DIR__ . '/../config/audit.php', 'audit');

        Event::listen(AuditCustom::class, RecordCustomAudit::class);
        Event::listen(DispatchAudit::class, ProcessDispatchAudit::class);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            AuditDriverCommand::class,
            AuditResolverCommand::class,
            InstallCommand::class,
        ]);

        $this->app->singleton(Auditor::class, function ($app) {
            return new \OwenIt\Auditing\Auditor($app);
        });
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    private function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            // Lumen lacks a config_path() helper, so we use base_path()
            $this->publishes([
                __DIR__ . '/../config/audit.php' => base_path('config/audit.php'),
            ], 'config');

            if (!class_exists('CreateAuditsTable')) {
                $this->publishes([
                    __DIR__ . '/../database/migrations/audits.stub' => database_path(
                        sprintf('migrations/%s_create_audits_table.php', date('Y_m_d_His'))
                    ),
                ], 'migrations');
            }
        }
    }
}
