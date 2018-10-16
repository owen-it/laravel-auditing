<?php

namespace OwenIt\Auditing\Tests;

use OwenIt\Auditing\AuditableObserver;
use OwenIt\Auditing\Tests\Models\Article;

class AuditableObserverTest extends AuditingTestCase
{
    /**
     * @group AuditableObserver::retrieved
     * @group AuditableObserver::created
     * @group AuditableObserver::updated
     * @group AuditableObserver::deleted
     * @group AuditableObserver::restoring
     * @group AuditableObserver::restored
     * @test
     *
     * @dataProvider auditableObserverTestProvider
     *
     * @param string $eventMethod
     * @param bool   $expectedBefore
     * @param bool   $expectedAfter
     */
    public function itExecutesTheAuditorSuccessfully(string $eventMethod, bool $expectedBefore, bool $expectedAfter)
    {
        $observer = new AuditableObserver();
        $model = factory(Article::class)->create();

        $this->assertSame($expectedBefore, $observer::$restoring);

        $observer->$eventMethod($model);

        $this->assertSame($expectedAfter, $observer::$restoring);
    }

    /**
     * @return array
     */
    public function auditableObserverTestProvider(): array
    {
        return [
            [
                'retrieved',
                false,
                false,
            ],
            [
                'created',
                false,
                false,
            ],
            [
                'updated',
                false,
                false,
            ],
            [
                'deleted',
                false,
                false,
            ],
            [
                'restoring',
                false,
                true,
            ],
            [
                'restored',
                true,
                false,
            ],
        ];
    }
}
