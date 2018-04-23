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

namespace OwenIt\Auditing\Tests\Functional;

use OwenIt\Auditing\Tests\AuditingTestCase;

class CommandTest extends AuditingTestCase
{
    /**
     * @test
     */
    public function itWillGenerateTheAuditMigration()
    {
        $migrationFilePath = sprintf(
            '%s/migrations/%s_create_audits_table.php',
            $this->app->databasePath(),
            date('Y_m_d_His')
        );

        $this->assertSame(0, $this->artisan('auditing:table'));

        $this->assertFileExists($migrationFilePath);

        $this->assertTrue(unlink($migrationFilePath));
    }

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

    /**
     * @test
     */
    public function itWillPublishTheVendorFiles()
    {
        $configFilePath = sprintf(
            '%s/audit.php',
            $this->app->configPath()
        );

        $migrationFilePath = sprintf(
            '%s/migrations/%s_create_audits_table.php',
            $this->app->databasePath(),
            date('Y_m_d_His')
        );

        $this->assertSame(0, $this->artisan('auditing:install'));

        $this->assertFileExists($migrationFilePath);
        $this->assertFileExists($configFilePath);

        $this->assertTrue(unlink($migrationFilePath));
        $this->assertTrue(unlink($configFilePath));
    }
}
