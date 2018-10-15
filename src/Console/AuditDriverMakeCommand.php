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
