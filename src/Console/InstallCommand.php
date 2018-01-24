<?php
/**
 * This file is part of the Laravel Auditing package.
 *
 * @author     Antério Vieira <anteriovieira@gmail.com>
 * @author     Quetzy Garcia  <quetzyg@altek.org>
 * @author     Raphael França <raphaelfrancabsb@gmail.com>
 * @copyright  2015-2018
 *
 * For the full copyright and license information,
 * please view the LICENSE.md file that was distributed
 * with this source code.
 */

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
    public function handle()
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
