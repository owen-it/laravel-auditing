<?php

namespace OwenIt\Auditing;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

use OwenIt\Auditing\Console\AuditDriverCommand;
use OwenIt\Auditing\Console\InstallCommand;
use OwenIt\Auditing\Contracts\Auditor;

class AuditingServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap the service provider.
     *
     * @return void
     */
    public function boot(Filesystem $filesystem)
    {
        $this->registerPublishing($filesystem);
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

        $this->app->singleton(Auditor::class, function ($app) {
            return new \OwenIt\Auditing\Auditor($app);
        });
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    private function registerPublishing($filesystem)
    {
        if ($this->app->runningInConsole()) {
            // Lumen lacks a config_path() helper, so we use base_path()
            $this->publishes([
                __DIR__.'/../config/audit.php' => base_path('config/audit.php'),
            ], 'config');

            if (!class_exists('CreateAuditsTable') && !$this->migrationAlreadyPublished(
                 $filesystem,
                 '_create_audits_table.php'
             )) {
                $this->publishes([
                    __DIR__.'/../database/migrations/audits.stub' => database_path(
                        sprintf('migrations/%s_create_audits_table.php', date('Y_m_d_His'))
                    ),
                ], 'migrations');
            }
        }
    }

    /**
     * @param Filesystem  $filesystem
     * @param $filename
     * @return bool
     */
    protected function migrationAlreadyPublished(Filesystem $filesystem, $filename): bool
    {
        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
                ->flatMap(function ($path) use ($filesystem, $filename) {
                    return $filesystem->glob($path.'*'.$filename);
                })
                ->count() > 0;
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
