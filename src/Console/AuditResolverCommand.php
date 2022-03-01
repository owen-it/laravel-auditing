<?php

namespace OwenIt\Auditing\Console;

use Illuminate\Console\GeneratorCommand;

class AuditResolverCommand extends GeneratorCommand
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'auditing:audit-resolver';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Create a new audit resolver';

    /**
     * {@inheritdoc}
     */
    protected $type = 'AuditResolver';

    /**
     * {@inheritdoc}
     */
    protected function getStub()
    {
        return __DIR__ . '/../../stubs/resolver.stub';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\AuditResolvers';
    }

    public function handle()
    {
        $this->info('Add your new resolver to the resolvers array in audit.php config file.');
        return parent::handle();
    }
}
