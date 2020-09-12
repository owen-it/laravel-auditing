<?php

namespace OwenIt\Auditing;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use OwenIt\Auditing\Console\AuditDriverCommand;
use OwenIt\Auditing\Console\InstallCommand;
use OwenIt\Auditing\Contracts\Auditor as AuditorContract;

class AuditingServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPublishing();
        $this->mergeConfigFrom(__DIR__.'/../config/audit.php', 'audit');
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
            InstallCommand::class,
        ]);

        $this->app->singleton(AuditorContract::class, function ($app) {
            return (new Auditor($app))->setContainer($app);
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
                __DIR__.'/../config/audit.php' => base_path('config/audit.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../database/migrations/audits.stub' => database_path(
                    sprintf('migrations/%s_create_audits_table.php', date('Y_m_d_His'))
                ),
            ], 'migrations');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function provides()
    {
        return [
            AuditorContract::class,
        ];
    }
}
