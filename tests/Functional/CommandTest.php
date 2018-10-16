<?php

namespace OwenIt\Auditing\Tests\Functional;

use OwenIt\Auditing\Tests\AuditingTestCase;

class CommandTest extends AuditingTestCase
{
    /**
     * @test
     */
    public function itWillGenerateTheAuditDriver()
    {
        $driverFilePath = sprintf(
            '%s/AuditDrivers/TestDriver.php',
            $this->app->path()
        );

        $this->assertSame(0, $this->artisan('make:audit-driver', [
            'name' => 'TestDriver',
        ]));

        $this->assertFileExists($driverFilePath);

        $this->assertTrue(unlink($driverFilePath));
    }
}
