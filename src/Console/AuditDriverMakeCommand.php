<?php

namespace OwenIt\Auditing\Console;

use Illuminate\Console\GeneratorCommand;

class AuditDriverMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:audit-driver';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new audit driver class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'AuditDriver';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/driver.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     *
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\AuditDrivers';
    }
}
