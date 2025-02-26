<?php

namespace OwenIt\Auditing\Tests\Unit;

use Illuminate\Support\Facades\Event;
use OwenIt\Auditing\AuditableObserver;
use OwenIt\Auditing\Events\DispatchAudit;
use OwenIt\Auditing\Events\DispatchingAudit;
use OwenIt\Auditing\Models\Audit;
use OwenIt\Auditing\Tests\AuditingTestCase;
use OwenIt\Auditing\Tests\Models\Article;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

class AuditableObserverTest extends AuditingTestCase
{
    #[DataProvider('auditableObserverDispatchTestProvider')]
    public function test_it_will_cancel_the_audit_dispatching_from_an_event_listener($eventMethod): void
    {
        Event::fake(
            [
                DispatchAudit::class,
            ]
        );

        Event::listen(DispatchingAudit::class, function () {
            return false;
        });

        $observer = new AuditableObserver;
        $model = Article::factory()->create();

        $observer->$eventMethod($model);

        $this->assertNull(Audit::first());

        Event::assertNotDispatched(DispatchAudit::class);
    }

    #[DataProvider('auditableObserverDispatchTestProvider')]
    public function test_it_dispatches_the_correct_events(string $eventMethod): void
    {
        Event::fake();

        $observer = new AuditableObserver;
        $model = Article::factory()->create();

        $observer->$eventMethod($model);

        Event::assertDispatched(DispatchingAudit::class, function ($event) use ($model) {
            return $event->model->is($model);
        });

        Event::assertDispatched(DispatchAudit::class, function ($event) use ($model) {
            return $event->model->is($model);
        });
    }

    #[Group('AuditableObserver::retrieved')]
    #[Group('AuditableObserver::created')]
    #[Group('AuditableObserver::updated')]
    #[Group('AuditableObserver::deleted')]
    #[Group('AuditableObserver::restoring')]
    #[Group('AuditableObserver::restored')]
    #[DataProvider('auditableObserverTestProvider')]
    public function test_it_executes_the_auditor_successfully(string $eventMethod, bool $expectedBefore, bool $expectedAfter): void
    {
        $observer = new AuditableObserver;
        $model = Article::factory()->create();

        $this->assertSame($expectedBefore, $observer::$restoring);

        $observer->$eventMethod($model);

        $this->assertSame($expectedAfter, $observer::$restoring);
    }

    public static function auditableObserverTestProvider(): array
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

    public static function auditableObserverDispatchTestProvider(): array
    {
        return [
            [
                'created',
            ],
            [
                'updated',
            ],
            [
                'deleted',
            ],
            [
                'restored',
            ],
        ];
    }
}
