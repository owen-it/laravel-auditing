<?php

namespace OwenIt\Auditing\Tests\Functional;

use OwenIt\Auditing\Tests\AuditingTestCase;

class CommandTest extends AuditingTestCase
{
    public function test_it_will_generate_the_audit_driver(): void
    {
        $driverFilePath = sprintf(
            '%s/AuditDrivers/TestDriver.php',
            $this->app->path()
        );

        $className = '\Illuminate\Testing\PendingCommand';
        if (class_exists('Illuminate\Foundation\Testing\PendingCommand')) {
            $className = '\Illuminate\Foundation\Testing\PendingCommand';
        }

        $this->assertInstanceOf(
            $className,
            $this->artisan(
                'auditing:audit-driver',
                [
                    'name' => 'TestDriver',
                ]
            )
        );

        $this->assertFileExists($driverFilePath);

        $this->assertTrue(unlink($driverFilePath));
    }
}
