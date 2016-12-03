<?php

namespace OwenIt\Auditing\Tests;

use Mockery;
use OwenIt\Auditing\AuditableObserver;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Facades\Auditor;

class AuditableObserverTest extends AbstractTestCase
{
    /**
     * Test the AuditableObserver class instantiation.
     *
     * @return AuditableObserver
     */
    public function testAuditableObserverInstantiation()
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
    public function testAuditableMock()
    {
        $model = Mockery::mock(Auditable::class);

        $this->assertInstanceOf(Auditable::class, $model);

        return $model;
    }

    /**
     * Test AuditableObserver methods to PASS.
     *
     * @depends testAuditableObserverInstantiation
     * @depends testAuditableMock
     *
     * @param AuditableObserver $observer
     * @param Auditable         $model
     *
     * @return void
     */
    public function testAuditableObserverMethodsPass(AuditableObserver $observer, Auditable $model)
    {
        Auditor::shouldReceive('execute')
            ->times(4)
            ->with($model);

        $methods = [
            'created',
            'updated',
            'deleted',
            'restored',
        ];

        foreach ($methods as $method) {
            $model->shouldReceive('setAuditEvent')
                ->with($method)
                ->andReturn($model);

            call_user_func([$observer, $method], $model);
        }
    }
}
