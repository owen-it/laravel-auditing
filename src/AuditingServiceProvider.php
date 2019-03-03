<?php

namespace OwenIt\Auditing;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use OwenIt\Auditing\Console\AuditDriverMakeCommand;
use OwenIt\Auditing\Contracts\Auditor;

class AuditingServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $config = __DIR__.'/../config/audit.php';
        $migration = __DIR__.'/../database/migrations/audits.stub';

        // Lumen lacks a config_path() helper, so we use base_path()
        $this->publishes([
            $config => base_path('config/audit.php'),
        ], 'config');

        $this->publishes([
            $migration => database_path(sprintf('migrations/%s_create_audits_table.php', date('Y_m_d_His'))),
        ], 'migrations');

        $this->mergeConfigFrom($config, 'audit');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            AuditDriverMakeCommand::class,
        ]);

        $this->app->singleton(Auditor::class, function ($app) {
            return new \OwenIt\Auditing\Auditor($app);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function provides()
    {
        return [
            Auditor::class,
        ];
    }
}
