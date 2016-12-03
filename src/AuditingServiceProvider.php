<?php

namespace OwenIt\Auditing;

use Illuminate\Support\ServiceProvider;
use OwenIt\Auditing\Console\AuditDriverMakeCommand;
use OwenIt\Auditing\Console\AuditTableCommand;
use OwenIt\Auditing\Console\InstallCommand;
use OwenIt\Auditing\Contracts\Auditor;

class AuditingServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    protected $defer = true;

    /**
     * Bootstrap the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupConfig($this->app);
    }

    /**
     * Setup the config.
     *
     * @param $app
     *
     * @return void
     */
    protected function setupConfig($app)
    {
        $config = realpath(__DIR__.'/../config/audit.php');

        if ($app->runningInConsole()) {
            $this->publishes([
                $config => config_path('audit.php'),
            ]);
        }

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
            AuditTableCommand::class,
            AuditDriverMakeCommand::class,
            InstallCommand::class,
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
