<?php

namespace OwenIt\Auditing\Tests\Unit;

use OwenIt\Auditing\AuditableObserver;
use OwenIt\Auditing\Tests\AuditingTestCase;
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
     *
     * @test
     *
     * @dataProvider auditableObserverTestProvider
     */
    public function itExecutesTheAuditorSuccessfully(string $eventMethod, bool $expectedBefore, bool $expectedAfter)
    {
        $observer = new AuditableObserver();
        $model = Article::factory()->create();

        $this->assertSame($expectedBefore, $observer::$restoring);

        $observer->$eventMethod($model);

        $this->assertSame($expectedAfter, $observer::$restoring);
    }

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
