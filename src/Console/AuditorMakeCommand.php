<?php

namespace OwenIt\Auditing\Console;

use Illuminate\Console\GeneratorCommand;

class AuditorMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:auditor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new auditor class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Auditor';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/auditor.stub';
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
        return $rootNamespace.'\Auditors';
    }
}
