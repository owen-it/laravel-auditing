<?php

namespace OwenIt\Auditing;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
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

        $this->app->bind(Auditor::class, function ($app) {
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
        if (! $this->app->runningInConsole()) {
            return;
        }

        // Lumen lacks a config_path() helper, so we use base_path()
        $this->publishes([
            __DIR__.'/../config/audit.php' => base_path('config/audit.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../database/migrations/audits.stub' => $this->getMigrationFileName('create_audits_table.php'),
        ], 'migrations');
    }

    /**
     * @return array<class-string>
     */
    public function provides()
    {
        return [
            Auditor::class,
        ];
    }

    /**
     * Returns existing migration file if found, else uses the current timestamp.
     */
    protected function getMigrationFileName(string $migrationFileName): string
    {
        $timestamp = date('Y_m_d_His');

        $filesystem = $this->app->make(Filesystem::class);

        return Collection::make([$this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR])
            ->flatMap(fn ($path) => $filesystem->glob($path.'*_'.$migrationFileName))
            ->push($this->app->databasePath()."/migrations/{$timestamp}_{$migrationFileName}")
            ->first();
    }
}
