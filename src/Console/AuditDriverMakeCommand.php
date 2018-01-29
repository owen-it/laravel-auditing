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

use Illuminate\Console\GeneratorCommand;

class AuditDriverMakeCommand extends GeneratorCommand
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'make:audit-driver';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Create a new audit driver';

    /**
     * {@inheritdoc}
     */
    protected $type = 'AuditDriver';

    /**
     * {@inheritdoc}
     */
    protected function getStub()
    {
        return __DIR__.'/../../drivers/driver.stub';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\AuditDrivers';
    }
}
