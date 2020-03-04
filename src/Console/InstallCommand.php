<?php

namespace OwenIt\Auditing\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $signature = 'auditing:install';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Install all of the Auditing resources';

    /**
     * {@inheritdoc}
     */
    public function handle(Application $app)
    {
        $this->comment('Publishing Auditing Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'config']);

        $this->comment('Publishing Auditing Migrations...');
        $this->callSilent('vendor:publish', ['--tag' => 'migrations']);

        $this->registerAuditingServiceProvider($app);

        $this->info('Auditing installed successfully.');
    }

    /**
     * Register the Auditing service provider in the application configuration file.
     *
     * @param Application $app
     * @return void
     */
    protected function registerAuditingServiceProvider(Application $app)
    {
        $namespace = Str::replaceLast('\\', '', $app->getNamespace());

        $appConfig = file_get_contents(config_path('app.php'));

        if (Str::contains($appConfig, 'OwenIt\\Auditing\\AuditingServiceProvider::class')) {
            return;
        }

        file_put_contents(config_path('app.php'), str_replace(
            "{$namespace}\\Providers\EventServiceProvider::class,".PHP_EOL,
            "{$namespace}\\Providers\EventServiceProvider::class,".PHP_EOL."        OwenIt\Auditing\AuditingServiceProvider::class,".PHP_EOL,
            $appConfig
        ));
    }
}
