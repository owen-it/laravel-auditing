<?php
/**
 * This file is part of the Laravel Auditing package.
 *
 * @author     Antério Vieira <anteriovieira@gmail.com>
 * @author     Quetzy Garcia  <quetzyg@altek.org>
 * @author     Raphael França <raphaelfrancabsb@gmail.com>
 * @copyright  2015-2017
 *
 * For the full copyright and license information,
 * please view the LICENSE.md file that was distributed
 * with this source code.
 */

namespace OwenIt\Auditing\Tests;

use Mockery;
use Orchestra\Testbench\TestCase;
use OwenIt\Auditing\AuditableObserver;
use OwenIt\Auditing\AuditingServiceProvider;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Facades\Auditor;

class AuditableObserverTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getPackageProviders($app)
    {
        return [
            AuditingServiceProvider::class,
        ];
    }

    /**
     * Test the AuditableObserver class instantiation.
     *
     * @return AuditableObserver
     */
    public function testAuditableObserverInstantiation(): AuditableObserver
    {
        $observer = new AuditableObserver();

        $this->assertInstanceOf(AuditableObserver::class, $observer);

        return $observer;
    }

    /**
     * Test Auditable class mock.
     *
     * @return Auditable
     */
    public function testAuditableMock(): Auditable
    {
        $model = Mockery::mock(Auditable::class);

        $this->assertInstanceOf(Auditable::class, $model);

        return $model;
    }

    /**
     * Test AuditableObserver created method to PASS.
     *
     * @depends testAuditableObserverInstantiation
     * @depends testAuditableMock
     *
     * @param AuditableObserver $observer
     * @param Auditable         $model
     *
     * @return void
     */
    public function testAuditableObserverCreatedPass(AuditableObserver $observer, Auditable $model)
    {
        Auditor::shouldReceive('execute')
            ->once()
            ->with($model);

        $model->shouldReceive('setAuditEvent')
            ->once()
            ->with('created')
            ->andReturn($model);

        $observer->created($model);

        $this->assertFalse($observer::$restoring);
    }

    /**
     * Test AuditableObserver updated method to PASS.
     *
     * @depends testAuditableObserverInstantiation
     * @depends testAuditableMock
     *
     * @param AuditableObserver $observer
     * @param Auditable         $model
     *
     * @return void
     */
    public function testAuditableObserverUpdatedPass(AuditableObserver $observer, Auditable $model)
    {
        Auditor::shouldReceive('execute')
            ->once()
            ->with($model);

        $model->shouldReceive('setAuditEvent')
            ->once()
            ->with('updated')
            ->andReturn($model);

        $observer->updated($model);

        $this->assertFalse($observer::$restoring);
    }

    /**
     * Test AuditableObserver deleted method to PASS.
     *
     * @depends testAuditableObserverInstantiation
     * @depends testAuditableMock
     *
     * @param AuditableObserver $observer
     * @param Auditable         $model
     *
     * @return void
     */
    public function testAuditableObserverDeletedPass(AuditableObserver $observer, Auditable $model)
    {
        Auditor::shouldReceive('execute')
            ->once()
            ->with($model);

        $model->shouldReceive('setAuditEvent')
            ->once()
            ->with('deleted')
            ->andReturn($model);

        $observer->deleted($model);

        $this->assertFalse($observer::$restoring);
    }

    /**
     * Test AuditableObserver restoring method to PASS.
     *
     * @depends testAuditableObserverInstantiation
     * @depends testAuditableMock
     *
     * @param AuditableObserver $observer
     * @param Auditable         $model
     *
     * @return void
     */
    public function testAuditableObserverRestoringPass(AuditableObserver $observer, Auditable $model)
    {
        $this->assertFalse($observer::$restoring);

        $observer->restoring($model);

        $this->assertTrue($observer::$restoring);
    }

    /**
     * Test AuditableObserver restored method to PASS.
     *
     * @depends testAuditableObserverInstantiation
     * @depends testAuditableMock
     *
     * @param AuditableObserver $observer
     * @param Auditable         $model
     *
     * @return void
     */
    public function testAuditableObserverRestoredPass(AuditableObserver $observer, Auditable $model)
    {
        Auditor::shouldReceive('execute')
            ->once()
            ->with($model);

        $model->shouldReceive('setAuditEvent')
            ->once()
            ->with('restored')
            ->andReturn($model);

        $observer->restored($model);

        $this->assertFalse($observer::$restoring);
    }
}
