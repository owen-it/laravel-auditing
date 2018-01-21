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

namespace OwenIt\Auditing\Tests\Functional;

use Carbon\Carbon;
use Illuminate\Support\Facades\App;
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

        $this->assertArraySubset([
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

        $this->assertArraySubset([
            'content'      => 'N/A',
            'published_at' => null,
            'reviewed'     => 0,
        ], $audit->old_values, true);

        $this->assertArraySubset([
            'content'      => 'First step: install the laravel-auditing package.',
            'published_at' => $now->format('Y-m-d H:i:s'),
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

        $this->assertArraySubset([
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

        $this->assertArraySubset([
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
            'title'        => 'How To Keep All Audit Records',
            'content'      => 'N/A',
            'published_at' => null,
            'reviewed'     => 0,
        ]);

        foreach (range(0, 100) as $count) {
            $article->update([
                'reviewed' => ($count % 2),
            ]);
        }

        $this->assertEquals(100, $article->audits()->count());
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
            'title'        => 'How To Keep The Most Recent Audit Records',
            'content'      => 'N/A',
            'published_at' => null,
            'reviewed'     => 0,
        ]);

        foreach (range(0, 100) as $count) {
            $article->update([
                'reviewed' => ($count % 2),
            ]);
        }

        $this->assertEquals(10, $article->audits()->count());
    }
}
