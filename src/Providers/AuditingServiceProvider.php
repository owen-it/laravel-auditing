<?php

namespace OwenIt\Auditing\Providers;

use Illuminate\Support\ServiceProvider;
use OwenIt\Auditing\AuditorManager;
use OwenIt\Auditing\Console\AuditingTableCommand;
use OwenIt\Auditing\Console\AuditorMakeCommand;
use OwenIt\Auditing\Console\InstallCommand;
use OwenIt\Auditing\Contracts\Dispatcher;

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
        $config = realpath(__DIR__.'/../config/auditing.php');

        if ($app->runningInConsole()) {
            $this->publishes([
                $config => config_path('auditing.php'),
            ]);
        }

        $this->mergeConfigFrom($config, 'auditing');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            AuditingTableCommand::class,
            AuditorMakeCommand::class,
            InstallCommand::class,
        ]);

        $this->app->singleton(AuditorManager::class, function ($app) {
            return new AuditorManager($app);
        });

        $this->app->alias(AuditorManager::class, Dispatcher::class);
    }

    /**
     * {@inheritdoc}
     */
    public function provides()
    {
        return [
            AuditorManager::class,
            Dispatcher::class,
        ];
    }
}
