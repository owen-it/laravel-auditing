<?php

namespace OwenIt\Auditing\Tests\Functional;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\Assert;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use OwenIt\Auditing\Events\Auditing;
use OwenIt\Auditing\Exceptions\AuditingException;
use OwenIt\Auditing\Models\Audit;
use OwenIt\Auditing\Tests\AuditingTestCase;
use OwenIt\Auditing\Tests\Models\Article;
use OwenIt\Auditing\Tests\Models\User;

class AuditingTest extends AuditingTestCase
{
    /**
     * @test
     */
    public function itWillNotAuditModelsWhenRunningFromTheConsole()
    {
        $this->app['config']->set('audit.console', false);

        factory(User::class)->create();

        $this->assertSame(1, User::query()->count());
        $this->assertSame(0, Audit::query()->count());
    }

    /**
     * @test
     */
    public function itWillAuditModelsWhenRunningFromTheConsole()
    {
        $this->app['config']->set('audit.console', true);

        factory(User::class)->create();

        $this->assertSame(1, User::query()->count());
        $this->assertSame(1, Audit::query()->count());
    }

    /**
     * @test
     */
    public function itWillAlwaysAuditModelsWhenNotRunningFromTheConsole()
    {
        App::shouldReceive('runningInConsole')
            ->andReturn(false);

        $this->app['config']->set('audit.console', false);

        factory(User::class)->create();

        $this->assertSame(1, User::query()->count());
        $this->assertSame(1, Audit::query()->count());
    }

    /**
     * @test
     */
    public function itWillNotAuditTheRetrievingEvent()
    {
        $this->app['config']->set('audit.console', true);

        factory(User::class)->create();

        $this->assertSame(1, User::query()->count());
        $this->assertSame(1, Audit::query()->count());

        User::first();

        $this->assertSame(1, Audit::query()->count());
        $this->assertSame(1, User::query()->count());
    }

    /**
     * @test
     */
    public function itWillAuditTheRetrievingEvent()
    {
        $this->app['config']->set('audit.console', true);
        $this->app['config']->set('audit.events', [
            'created',
            'retrieved',
        ]);

        factory(User::class)->create();

        $this->assertSame(1, User::query()->count());
        $this->assertSame(1, Audit::query()->count());

        User::first();

        $this->assertSame(1, User::query()->count());
        $this->assertSame(2, Audit::query()->count());
    }

    /**
     * @test
     */
    public function itWillAuditTheRetrievedEvent()
    {
        $this->app['config']->set('audit.events', [
            'retrieved',
        ]);

        factory(Article::class)->create([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'N/A',
            'published_at' => null,
            'reviewed'     => 0,
        ]);

        Article::first();

        $audit = Audit::first();

        $this->assertEmpty($audit->old_values);

        $this->assertEmpty($audit->new_values);
    }

    /**
     * @test
     */
    public function itWillAuditTheCreatedEvent()
    {
        $this->app['config']->set('audit.events', [
            'created',
        ]);

        factory(Article::class)->create([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'N/A',
            'published_at' => null,
            'reviewed'     => 0,
        ]);

        $audit = Audit::first();

        $this->assertEmpty($audit->old_values);

        Assert::assertArraySubset([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'N/A',
            'published_at' => null,
            'reviewed'     => 0,
            'id'           => 1,
        ], $audit->new_values, true);
    }

    /**
     * @test
     */
    public function itWillAuditTheUpdatedEvent()
    {
        $this->app['config']->set('audit.events', [
            'updated',
        ]);

        $article = factory(Article::class)->create([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'N/A',
            'published_at' => null,
            'reviewed'     => 0,
        ]);

        $now = Carbon::now();

        $article->update([
            'content'      => 'First step: install the laravel-auditing package.',
            'published_at' => $now,
            'reviewed'     => 1,
        ]);

        $audit = Audit::first();

        Assert::assertArraySubset([
            'content'      => 'N/A',
            'published_at' => null,
            'reviewed'     => 0,
        ], $audit->old_values, true);

        Assert::assertArraySubset([
            'content'      => 'First step: install the laravel-auditing package.',
            'published_at' => $now->toDateTimeString(),
            'reviewed'     => 1,
        ], $audit->new_values, true);
    }

    /**
     * @test
     */
    public function itWillAuditTheDeletedEvent()
    {
        $this->app['config']->set('audit.events', [
            'deleted',
        ]);

        $article = factory(Article::class)->create([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'N/A',
            'published_at' => null,
            'reviewed'     => 0,
        ]);

        $article->delete();

        $audit = Audit::first();

        Assert::assertArraySubset([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'N/A',
            'published_at' => null,
            'reviewed'     => 0,
            'id'           => 1,
        ], $audit->old_values, true);

        $this->assertEmpty($audit->new_values);
    }

    /**
     * @test
     */
    public function itWillAuditTheRestoredEvent()
    {
        $this->app['config']->set('audit.events', [
            'restored',
        ]);

        $article = factory(Article::class)->create([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'N/A',
            'published_at' => null,
            'reviewed'     => 0,
        ]);

        $article->delete();
        $article->restore();

        $audit = Audit::first();

        $this->assertEmpty($audit->old_values);

        Assert::assertArraySubset([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'N/A',
            'published_at' => null,
            'reviewed'     => 0,
            'id'           => 1,
        ], $audit->new_values, true);
    }

    /**
     * @test
     */
    public function itWillKeepAllAudits()
    {
        $this->app['config']->set('audit.threshold', 0);
        $this->app['config']->set('audit.events', [
            'updated',
        ]);

        $article = factory(Article::class)->create([
            'reviewed' => 1,
        ]);

        foreach (range(0, 99) as $count) {
            $article->update([
                'reviewed' => ($count % 2),
            ]);
        }

        $this->assertSame(100, $article->audits()->count());
    }

    /**
     * @test
     */
    public function itWillRemoveOlderAuditsAboveTheThreshold()
    {
        $this->app['config']->set('audit.threshold', 10);
        $this->app['config']->set('audit.events', [
            'updated',
        ]);

        $article = factory(Article::class)->create([
            'reviewed' => 1,
        ]);

        foreach (range(0, 99) as $count) {
            $article->update([
                'reviewed' => ($count % 2),
            ]);
        }

        $this->assertSame(10, $article->audits()->count());
    }

    /**
     * @test
     */
    public function itWillNotAuditDueToUnsupportedDriver()
    {
        $this->app['config']->set('audit.driver', 'foo');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Driver [foo] not supported.');

        factory(Article::class)->create();
    }

    /**
     * @test
     */
    public function itWillNotAuditDueToClassWithoutDriverInterface()
    {
        // We just pass a FQCN that does not implement the AuditDriver interface
        $this->app['config']->set('audit.driver', self::class);

        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('The driver must implement the AuditDriver contract');

        factory(Article::class)->create();
    }

    /**
     * @test
     */
    public function itWillAuditUsingTheDefaultDriver()
    {
        $this->app['config']->set('audit.driver', null);

        factory(Article::class)->create([
            'title'        => 'How To Audit Using The Fallback Driver',
            'content'      => 'N/A',
            'published_at' => null,
            'reviewed'     => 0,
        ]);

        $audit = Audit::first();

        $this->assertEmpty($audit->old_values);

        Assert::assertArraySubset([
            'title'        => 'How To Audit Using The Fallback Driver',
            'content'      => 'N/A',
            'published_at' => null,
            'reviewed'     => 0,
            'id'           => 1,
        ], $audit->new_values, true);
    }

    /**
     * @test
     */
    public function itWillCancelTheAuditFromAnEventListener()
    {
        Event::listen(Auditing::class, function () {
            return false;
        });

        factory(Article::class)->create();

        $this->assertNull(Audit::first());
    }

    /**
     * @test
     */
    public function itDisablesAndEnablesAuditingBackAgain()
    {
        // Auditing is enabled by default
        $this->assertFalse(Article::$auditingDisabled);

        factory(Article::class)->create();

        $this->assertSame(1, Article::count());
        $this->assertSame(1, Audit::count());

        // Disable Auditing
        Article::disableAuditing();
        $this->assertTrue(Article::$auditingDisabled);

        factory(Article::class)->create();

        $this->assertSame(2, Article::count());
        $this->assertSame(1, Audit::count());

        // Re-enable Auditing
        Article::enableAuditing();
        $this->assertFalse(Article::$auditingDisabled);

        factory(Article::class)->create();

        $this->assertSame(2, Audit::count());
        $this->assertSame(3, Article::count());
    }
}
