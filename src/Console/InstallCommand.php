<?php

namespace OwenIt\Auditing\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use OwenIt\Auditing\AuditingServiceProvider;

class InstallCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'auditing:install';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Install the Laravel Auditing package';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->info('Publishing the config files');
        Artisan::call('vendor:publish', [
            '--provider' => AuditingServiceProvider::class,
        ]);

        $this->info('Publishing the migration file');
        Artisan::call('auditing:table');

        $this->info('Successfully installed Laravel Auditing! Enjoy :)');
    }
}
