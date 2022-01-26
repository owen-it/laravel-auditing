<?php

namespace OwenIt\Auditing\Console;

use Illuminate\Console\GeneratorCommand;

class AuditResolverCommand extends GeneratorCommand
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'auditing:resolver';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Create a new resolver';

    /**
     * {@inheritdoc}
     */
    protected $type = 'AuditResolver';

    /**
     * {@inheritdoc}
     */
    protected function getStub()
    {
        return __DIR__.'/../../stubs/resolver.stub';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Resolvers';
    }
}
