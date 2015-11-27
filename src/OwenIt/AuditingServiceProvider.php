<?php

namespace OwenIt\Auditing;

use Illuminate\Support\ServiceProvider;

/**
 * This is the owen auditing service provider class.
 */
class AuditingServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupMigrations();
    }

    /**
     * Setup the migrations.
     *
     * @return void
     */
    protected function setupMigrations()
    {
        $source = realpath(__DIR__.'/../migrations/');

        $this->publishes([$source => database_path('migrations')], 'migrations');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
