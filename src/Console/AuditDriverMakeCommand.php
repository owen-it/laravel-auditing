<?php

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
    protected $description = 'Create a new driver for auditing';

    /**
     * {@inheritdoc}
     */
    protected $type = 'AuditDriver';

    /**
     * {@inheritdoc}
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/driver.stub';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\AuditDrivers';
    }
}
