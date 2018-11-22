<?php

namespace OwenIt\Auditing;

use Illuminate\Support\ServiceProvider;
use OwenIt\Auditing\Console\AuditDriverMakeCommand;
use OwenIt\Auditing\Contracts\Auditor;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;


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
        $config = __DIR__.'/../config/audit.php';
        $migration = __DIR__.'/../database/migrations/audits.stub';

        // Lumen lacks a config_path() helper, so we use base_path()
        $this->publishes([
            $config => base_path('config/audit.php'),
        ], 'config');

        $this->publishes([
            $migration => database_path('migrations/'.$this->deprecatedFileName()),
        ], 'migrations');

        $this->mergeConfigFrom($config, 'audit');
    }

    /**
     * Check to see if package has been used previously.
     * If it has, use that file name, if not, use a file name that timestamps the day of this pull request
     * @return string
     */
    protected function deprecatedFileName()
    {
        $iterator = iterator_to_array(new RecursiveIteratorIterator(
            new RecursiveRegexIterator(
                new RecursiveDirectoryIterator(database_path('migrations'), RecursiveDirectoryIterator::KEY_AS_PATHNAME|RecursiveDirectoryIterator::SKIP_DOTS),
                '/(\d{4})_(\d{2})_(\d{2})_(\d{6})_create_audits_table\.php/i', RecursiveRegexIterator::MATCH
            ),
            RecursiveIteratorIterator::SELF_FIRST
        ));

        return count($iterator) ? reset($iterator)->getFilename() : '2018_11_22_000000_create_audits_table.php';
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
