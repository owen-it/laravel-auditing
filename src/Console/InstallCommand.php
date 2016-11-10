<?php

namespace OwenIt\Auditing\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'auditing:install';

    /**
     * The console command description.
     *
     * @var string
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
        Artisan::call('vendor:publish', ['--provider' => 'OwenIt\Auditing\AuditingServiceProvider']);

        $this->info('Publishing the migration file');
        Artisan::call('auditing:table');

        $this->info('Successfully installed Laravel Auditing! Enjoy :)');
    }
}
