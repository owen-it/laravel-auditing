<?php

namespace OwenIt\Auditing\Tests\Functional;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\Assert;
use InvalidArgumentException;
use OwenIt\Auditing\Events\AuditCustom;
use OwenIt\Auditing\Events\Audited;
use OwenIt\Auditing\Events\Auditing;
use OwenIt\Auditing\Exceptions\AuditingException;
use OwenIt\Auditing\Models\Audit;
use OwenIt\Auditing\Tests\AuditingTestCase;
use OwenIt\Auditing\Tests\fixtures\TenantResolver;
use OwenIt\Auditing\Tests\Models\Article;
use OwenIt\Auditing\Tests\Models\ArticleCustomAuditMorph;
use OwenIt\Auditing\Tests\Models\ArticleExcludes;
use OwenIt\Auditing\Tests\Models\Category;
use OwenIt\Auditing\Tests\Models\User;

class AuditingTest extends AuditingTestCase
{
    use WithFaker;

    /**
     * @test
     */
    public function it_will_not_audit_models_when_running_from_the_console()
    {
        $this->app['config']->set('audit.console', false);

        factory(User::class)->create();

        $this->assertSame(1, User::query()->count());
        $this->assertSame(0, Audit::query()->count());
    }

    /**
     * @test
     */
    public function it_will_audit_models_when_running_from_the_console()
    {
        $this->app['config']->set('audit.console', true);

        factory(User::class)->create();

        $this->assertSame(1, User::query()->count());
        $this->assertSame(1, Audit::query()->count());
    }

    /**
     * @test
     */
    public function it_will_always_audit_models_when_not_running_from_the_console()
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
    public function it_will_not_audit_the_retrieving_event()
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
    public function it_will_audit_the_retrieving_event()
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
    public function it_will_audit_the_retrieved_event()
    {
        $this->app['config']->set('audit.events', [
            'retrieved',
        ]);

        factory(Article::class)->create([
            'title' => 'How To Audit Eloquent Models',
            'content' => 'N/A',
            'published_at' => null,
            'reviewed' => 0,
        ]);

        Article::first();

        $audit = Audit::first();

        $this->assertNotNull($audit);

        $this->assertEmpty($audit->old_values);

        $this->assertEmpty($audit->new_values);
    }

    /**
     * @test
     */
    public function it_will_audit_the_created_event()
    {
        $this->app['config']->set('audit.events', [
            'created',
        ]);

        factory(Article::class)->create([
            'title' => 'How To Audit Eloquent Models',
            'content' => 'N/A',
            'published_at' => null,
            'reviewed' => 0,
        ]);

        $audit = Audit::first();

        $this->assertNotNull($audit);

        $this->assertEmpty($audit->old_values);

        Assert::assertArraySubset([
            'title' => 'How To Audit Eloquent Models',
            'content' => 'N/A',
            'published_at' => null,
            'reviewed' => 0,
            'id' => 1,
        ], $audit->new_values, true);
    }

    /**
     * @test
     */
    public function it_will_audit_the_updated_event()
    {
        $this->app['config']->set('audit.events', [
            'updated',
        ]);

        $article = factory(Article::class)->create([
            'title' => 'How To Audit Eloquent Models',
            'content' => 'N/A',
            'published_at' => null,
            'reviewed' => 0,
        ]);

        $now = Carbon::now();

        $article->update([
            'content' => 'First step: install the laravel-auditing package.',
            'published_at' => $now,
            'reviewed' => 1,
        ]);

        $audit = Audit::first();

        $this->assertNotNull($audit);

        Assert::assertArraySubset([
            'content' => 'N/A',
            'published_at' => null,
            'reviewed' => 0,
        ], $audit->old_values, true);

        Assert::assertArraySubset([
            'content' => Article::contentMutate('First step: install the laravel-auditing package.'),
            'published_at' => $now->toDateTimeString(),
            'reviewed' => 1,
        ], $audit->new_values, true);
    }

    /**
     * @test
     */
    public function it_will_audit_the_deleted_event()
    {
        $this->app['config']->set('audit.events', [
            'deleted',
        ]);

        $article = factory(Article::class)->create([
            'title' => 'How To Audit Eloquent Models',
            'content' => 'N/A',
            'published_at' => null,
            'reviewed' => 0,
        ]);

        $article->delete();

        $audit = Audit::first();

        $this->assertNotNull($audit);

        Assert::assertArraySubset([
            'title' => 'How To Audit Eloquent Models',
            'content' => 'N/A',
            'published_at' => null,
            'reviewed' => 0,
            'id' => 1,
        ], $audit->old_values, true);

        $this->assertEmpty($audit->new_values);
    }

    /**
     * @test
     */
    public function it_will_audit_the_restored_event()
    {
        $this->app['config']->set('audit.events', [
            'restored',
        ]);

        $article = factory(Article::class)->create([
            'title' => 'How To Audit Eloquent Models',
            'content' => 'N/A',
            'published_at' => null,
            'reviewed' => 0,
        ]);

        $article->delete();
        $article->restore();

        $audit = Audit::first();

        $this->assertNotNull($audit);

        $this->assertEmpty($audit->old_values);

        Assert::assertArraySubset([
            'title' => 'How To Audit Eloquent Models',
            'content' => 'N/A',
            'published_at' => null,
            'reviewed' => 0,
            'id' => 1,
        ], $audit->new_values, true);
    }

    /**
     * @test
     */
    public function it_will_keep_all_audits()
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
    public function it_will_remove_older_audits_above_the_threshold()
    {
        $this->app['config']->set('audit.threshold', 10);
        $this->app['config']->set('audit.events', [
            'updated',
        ]);

        $article = factory(Article::class)->create([
            'title' => 'Title #0',
        ]);

        foreach (range(1, 20) as $count) {
            if ($count === 11) {
                sleep(1);
            }

            $article->update([
                'title' => 'Title #'.$count,
            ]);
        }

        $audits = $article->audits()->get();
        $this->assertSame(10, $audits->count());
        $this->assertSame('Title #11', $audits->first()->new_values['title']);
        $this->assertSame('Title #20', $audits->last()->new_values['title']);
    }

    /**
     * @test
     */
    public function it_will_not_audit_due_to_unsupported_driver()
    {
        $this->app['config']->set('audit.driver', 'foo');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Driver [foo] not supported.');

        factory(Article::class)->create();
    }

    /**
     * @test
     */
    public function it_will_not_audit_due_to_class_without_driver_interface()
    {
        // We just pass a FQCN that does not implement the AuditDriver interface
        $this->app['config']->set('audit.driver', Article::class);

        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('The driver must implement the AuditDriver contract');

        factory(Article::class)->create();
    }

    /**
     * @test
     */
    public function it_will_audit_using_the_default_driver()
    {
        $this->app['config']->set('audit.driver', null);

        factory(Article::class)->create([
            'title' => 'How To Audit Using The Fallback Driver',
            'content' => 'N/A',
            'published_at' => null,
            'reviewed' => 0,
        ]);

        $audit = Audit::first();

        $this->assertNotNull($audit);

        $this->assertEmpty($audit->old_values);

        Assert::assertArraySubset([
            'title' => 'How To Audit Using The Fallback Driver',
            'content' => 'N/A',
            'published_at' => null,
            'reviewed' => 0,
            'id' => 1,
        ], $audit->new_values, true);
    }

    /**
     * @test
     */
    public function it_will_cancel_the_audit_from_an_event_listener()
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
    public function it_disables_and_enables_auditing_back_again()
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

    /**
     * @test
     */
    public function it_disables_and_enables_auditing_back_again_via_facade()
    {
        // Auditing is enabled by default
        $this->assertFalse(Article::$auditingDisabled);

        Article::disableAuditing();

        factory(Article::class)->create();

        $this->assertSame(1, Article::count());
        $this->assertSame(0, Audit::count());

        // Enable Auditing
        Article::enableAuditing();
        $this->assertFalse(Article::$auditingDisabled);

        factory(Article::class)->create();

        $this->assertSame(2, Article::count());
        $this->assertSame(1, Audit::count());
    }

    /**
     * @test
     */
    public function it_disables_and_enables_auditing_back_again_via_without_auditing_method()
    {
        // Auditing is enabled by default
        $this->assertFalse(Article::$auditingDisabled);

        Article::withoutAuditing(function () {
            factory(Article::class)->create();
        });

        $this->assertSame(1, Article::count());
        $this->assertSame(0, Audit::count());

        $this->assertFalse(Article::$auditingDisabled);

        factory(Article::class)->create();

        $this->assertSame(2, Article::count());
        $this->assertSame(1, Audit::count());
    }

    /**
     * @test
     *
     * @return void
     */
    public function it_handles_json_columns_correctly()
    {
        $article = factory(Article::class)->create(['config' => ['articleIsGood' => true, 'authorsJob' => 'vampire']]);
        $article->refresh();

        $article->config = ['articleIsGood' => false, 'authorsJob' => 'vampire'];
        $article->save();

        $audit = $article->audits()->skip(1)->first();

        $this->assertNotNull($audit);

        $this->assertFalse($audit->getModified()['config']['new']['articleIsGood']);
        $this->assertTrue($audit->getModified()['config']['old']['articleIsGood']);
    }

    /**
     * @return void
     *
     * @test
     */
    public function can_add_additional_resolver()
    {
        // added new resolver
        $this->app['config']->set('audit.resolvers.tenant_id', TenantResolver::class);

        $article = factory(Article::class)->create();

        $audit = $article->audits()->first();

        $this->assertNotNull($audit);

        $this->assertSame(1, (int) $audit->tenant_id);
    }

    /**
     * @return void
     *
     * @test
     */
    public function can_disable_resolver()
    {
        // added new resolver
        $this->app['config']->set('audit.resolvers.ip_address', null);

        $article = factory(Article::class)->create();

        $audit = $article->audits()->first();

        $this->assertNotNull($audit);

        $this->assertEmpty($audit->ip_address);
    }

    /**
     * @test
     *
     * @return void
     */
    public function it_will_exclude_if_global_exclude_is_set()
    {
        $this->app['config']->set('audit.exclude', ['content']);

        $article = new Article;
        $article->title = $this->faker->unique()->sentence;
        $article->content = $this->faker->unique()->paragraph(6);
        $article->published_at = null;
        $article->reviewed = 0;
        $article->save();
        $this->assertArrayNotHasKey('content', $article->audits()->first()->getModified());
    }

    /**
     * @test
     *
     * @return void
     */
    public function local_exclude_overrides_global_exclude()
    {
        $this->app['config']->set('audit.exclude', ['content']);

        $article = new ArticleExcludes;
        $article->title = $this->faker->unique()->sentence;
        $article->content = $this->faker->unique()->paragraph(6);
        $article->published_at = null;
        $article->reviewed = 0;
        $article->save();
        $this->assertArrayHasKey('content', $article->audits()->first()->getModified());
        $this->assertArrayNotHasKey('title', $article->audits()->first()->getModified());
    }

    /**
     * @test
     */
    public function it_will_not_audit_models_when_values_are_empty()
    {
        $this->app['config']->set('audit.empty_values', false);

        $article = new ArticleExcludes;
        $article->auditExclude = [];
        $article->title = $this->faker->unique()->sentence;
        $article->content = $this->faker->unique()->paragraph(6);
        $article->published_at = null;
        $article->reviewed = 0;
        $article->save();

        $article->auditExclude = [
            'reviewed',
        ];

        $article->reviewed = 1;
        $article->save();

        $this->assertSame(1, Article::query()->count());
        $this->assertSame(1, Audit::query()->count());
    }

    /**
     * @return void
     *
     * @test
     */
    public function it_will_audit_retrieved_event_even_if_audit_empty_is_disabled()
    {
        $this->app['config']->set('audit.empty_values', false);
        $this->app['config']->set('audit.allowed_empty_values', ['retrieved']);
        $this->app['config']->set('audit.events', [
            'created',
            'retrieved',
        ]);

        $this->app['config']->set('audit.empty_values', false);

        /** @var Article $model */
        factory(Article::class)->create();

        Article::find(1);

        $this->assertSame(2, Audit::query()->count());
    }

    /**
     * @test
     */
    public function it_will_audit_models_when_values_are_empty()
    {
        $model = factory(Article::class)->create([
            'reviewed' => 0,
        ]);

        $model->reviewed = 1;
        $model->save();

        $this->assertSame(1, Article::query()->count());
        $this->assertSame(2, Audit::query()->count());
    }

    /**
     * @test
     *
     * @return void
     */
    public function it_will_audit_attach()
    {
        $firstCategory = factory(Category::class)->create();
        $secondCategory = factory(Category::class)->create();
        $article = factory(Article::class)->create();

        $article->auditAttach('categories', $firstCategory);
        $article->auditAttach('categories', $secondCategory);
        $lastArticleAudit = $article->audits->last()->getModified()['categories'];

        $this->assertSame($firstCategory->name, $article->categories->first()->name);
        $this->assertSame(0, count($lastArticleAudit['old']));
        $this->assertSame(1, count($lastArticleAudit['new']));
        $this->assertSame($secondCategory->name, $lastArticleAudit['new'][0]['name']);
    }

    /**
     * @test
     *
     * @return void
     */
    public function it_will_not_audit_attach_by_invalid_relation_name()
    {
        $firstCategory = factory(Category::class)->create();
        $article = factory(Article::class)->create();

        $this->expectExceptionMessage('Relationship invalidRelation was not found or does not support method attach');

        $article->auditAttach('invalidRelation', $firstCategory);
    }

    /**
     * @test
     *
     * @return void
     */
    public function it_will_audit_sync()
    {
        $firstCategory = factory(Category::class)->create();
        $secondCategory = factory(Category::class)->create();
        $article = factory(Article::class)->create();

        $article->categories()->attach($firstCategory);

        $no_of_audits_before = Audit::where('auditable_type', Article::class)->count();
        $categoryBefore = $article->categories()->first()->getKey();

        $article->auditSync('categories', [$secondCategory->getKey()]);

        $no_of_audits_after = Audit::where('auditable_type', Article::class)->count();
        $categoryAfter = $article->categories()->first()->getKey();

        $this->assertSame($firstCategory->getKey(), $categoryBefore);
        $this->assertSame($secondCategory->getKey(), $categoryAfter);
        $this->assertNotSame($categoryBefore, $categoryAfter);
        $this->assertGreaterThan($no_of_audits_before, $no_of_audits_after);
    }

    /**
     * @test
     *
     * @return void
     */
    public function it_will_audit_sync_individually()
    {
        Article::disableAuditing();
        $user = factory(User::class)->create();
        $category = factory(Category::class)->create();
        $article = factory(Article::class)->create();
        Article::enableAuditing();

        $no_of_audits_before = Audit::where('auditable_type', Article::class)->count();
        $article->auditSync('users', [$user->getKey()]);
        $article->auditSync('categories', [$category->getKey()]);
        $audits = $article->audits()->get();
        $auditFirst = $audits->first();
        $auditLast = $audits->last();

        $this->assertSame($no_of_audits_before + 2, $audits->count());
        $this->assertSame($user->getKey(), $article->users()->first()->getKey());
        $this->assertSame($category->getKey(), $article->categories()->first()->getKey());

        $this->assertArrayHasKey('users', $auditFirst->new_values);
        $this->assertArrayHasKey('users', $auditFirst->old_values);
        $this->assertArrayNotHasKey('categories', $auditFirst->new_values);
        $this->assertArrayNotHasKey('categories', $auditFirst->old_values);

        $this->assertArrayHasKey('categories', $auditLast->new_values);
        $this->assertArrayHasKey('categories', $auditLast->old_values);
        $this->assertArrayNotHasKey('users', $auditLast->new_values);
        $this->assertArrayNotHasKey('users', $auditLast->old_values);
    }

    /**
     * @test
     *
     * @return void
     */
    public function it_will_audit_sync_with_pivot_values()
    {
        if (version_compare($this->app->version(), '8.0.0', '<')) {
            $this->markTestSkipped('This test is only for Laravel 8.0.0+');
        }

        $firstCategory = factory(Category::class)->create();
        $secondCategory = factory(Category::class)->create();
        $article = factory(Article::class)->create();

        $article->categories()->attach([$firstCategory->getKey() => ['pivot_type' => 'PIVOT_1']]);

        $no_of_audits_before = Audit::where('auditable_type', Article::class)->count();
        $categoryBefore = $article->categories()->first()->getKey();

        $article->auditSyncWithPivotValues(
            'categories',
            $secondCategory,
            ['pivot_type' => 'PIVOT_1']
        );

        $no_of_audits_after = Audit::where('auditable_type', Article::class)->count();
        $categoryAfter = $article->categories()->first()->getKey();

        $this->assertSame($firstCategory->getKey(), $categoryBefore);
        $this->assertSame($secondCategory->getKey(), $categoryAfter);
        $this->assertGreaterThan($no_of_audits_before, $no_of_audits_after);

        $this->assertSame(
            "{$secondCategory->getKey()}",
            $article->categories()->pluck('id')->join(',')
        );

        $this->assertSame(
            $secondCategory->getKey(),
            $article->categories()->wherePivot('pivot_type', 'PIVOT_1')->first()->getKey()
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function it_will_audit_sync_by_closure()
    {
        $firstCategory = factory(Category::class)->create();
        $secondCategory = factory(Category::class)->create();
        $thirdCategory = factory(Category::class)->create();
        $article = factory(Article::class)->create();

        $article->categories()->attach([$firstCategory->getKey() => ['pivot_type' => 'PIVOT_1']]);
        $article->categories()->attach([$secondCategory->getKey() => ['pivot_type' => 'PIVOT_2']]);

        $no_of_audits_before = Audit::where('auditable_type', Article::class)->count();
        $categoryBefore = $article->categories()->first()->getKey();

        $article->auditSync(
            'categories',
            [$thirdCategory->getKey() => ['pivot_type' => 'PIVOT_1']],
            true,
            ['*'],
            function ($categories) {
                return $categories->wherePivot('pivot_type', 'PIVOT_1');
            }
        );

        $no_of_audits_after = Audit::where('auditable_type', Article::class)->count();
        $categoryAfter = $article->categories()->first()->getKey();

        $this->assertSame($firstCategory->getKey(), $categoryBefore);
        $this->assertSame($secondCategory->getKey(), $categoryAfter);
        $this->assertGreaterThan($no_of_audits_before, $no_of_audits_after);

        $this->assertSame(
            "{$secondCategory->getKey()},{$thirdCategory->getKey()}",
            $article->categories()->pluck('id')->join(',')
        );

        $this->assertSame(
            $secondCategory->getKey(),
            $article->categories()->wherePivot('pivot_type', 'PIVOT_2')->first()->getKey()
        );

        $this->assertSame(
            $thirdCategory->getKey(),
            $article->categories()->wherePivot('pivot_type', 'PIVOT_1')->first()->getKey()
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function it_will_not_audit_sync_by_invalid_closure()
    {
        $firstCategory = factory(Category::class)->create();
        $secondCategory = factory(Category::class)->create();
        $article = factory(Article::class)->create();

        $article->categories()->attach($firstCategory);

        $this->expectException(QueryException::class);

        $article->auditSync(
            'categories',
            [$secondCategory->getKey()],
            true,
            ['*'],
            function ($categories) {
                return $categories->wherePivot('invalid_pivot_column', 'PIVOT_1');
            }
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function it_will_audit_detach()
    {
        $firstCategory = factory(Category::class)->create();
        $secondCategory = factory(Category::class)->create();
        $article = factory(Article::class)->create();

        $article->categories()->attach($firstCategory);
        $article->categories()->attach($secondCategory);

        $no_of_audits_before = Audit::where('auditable_type', Article::class)->count();
        $categoryBefore = $article->categories()->first()->getKey();

        $article->auditDetach('categories', [$firstCategory->getKey()]);

        $no_of_audits_after = Audit::where('auditable_type', Article::class)->count();
        $categoryAfter = $article->categories()->first()->getKey();

        $this->assertSame($firstCategory->getKey(), $categoryBefore);
        $this->assertSame($secondCategory->getKey(), $categoryAfter);
        $this->assertNotSame($categoryBefore, $categoryAfter);
        $this->assertGreaterThan($no_of_audits_before, $no_of_audits_after);
    }

    /**
     * @test
     *
     * @return void
     */
    public function it_will_audit_detach_by_closure()
    {
        $firstCategory = factory(Category::class)->create();
        $secondCategory = factory(Category::class)->create();
        $thirdCategory = factory(Category::class)->create();
        $article = factory(Article::class)->create();

        $article->categories()->attach([$firstCategory->getKey() => ['pivot_type' => 'PIVOT_1']]);
        $article->categories()->attach([$secondCategory->getKey() => ['pivot_type' => 'PIVOT_2']]);
        $article->categories()->attach([$thirdCategory->getKey() => ['pivot_type' => 'PIVOT_2']]);

        $no_of_audits_before = Audit::where('auditable_type', Article::class)->count();
        $categoryBefore = $article->categories()->first()->getKey();

        $article->auditDetach(
            'categories',
            [$firstCategory->getKey(), $secondCategory->getKey(), $thirdCategory->getKey()],
            true,
            ['*'],
            function ($categories) {
                return $categories->wherePivot('pivot_type', 'PIVOT_1');
            }
        );

        $no_of_audits_after = Audit::where('auditable_type', Article::class)->count();
        $categoryAfter = $article->categories()->first()->getKey();

        $this->assertSame($firstCategory->getKey(), $categoryBefore);
        $this->assertSame($secondCategory->getKey(), $categoryAfter);
        $this->assertNotSame($categoryBefore, $categoryAfter);
        $this->assertGreaterThan($no_of_audits_before, $no_of_audits_after);

        $this->assertSame(
            "{$secondCategory->getKey()},{$thirdCategory->getKey()}",
            $article->categories()->pluck('id')->join(',')
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function it_will_not_audit_detach_by_invalid_closure()
    {
        $firstCategory = factory(Category::class)->create();
        $article = factory(Article::class)->create();

        $article->categories()->attach($firstCategory);

        $this->expectExceptionMessage('Invalid Closure for categories Relationship');

        $article->auditDetach(
            'categories',
            [$firstCategory->getKey()],
            true,
            ['*'],
            function ($categories) {
                return $categories->invalid();
            }
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function it_will_audit_sync_without_changes()
    {
        $firstCategory = factory(Category::class)->create();
        $article = factory(Article::class)->create();

        $article->categories()->attach($firstCategory);

        $no_of_audits_before = Audit::where('auditable_type', Article::class)->count();
        $categoryBefore = $article->categories()->first()->getKey();

        $article->auditSync('categories', [$firstCategory->getKey()]);

        $no_of_audits_after = Audit::where('auditable_type', Article::class)->count();
        $categoryAfter = $article->categories()->first()->getKey();

        $this->assertSame($firstCategory->getKey(), $categoryBefore);
        $this->assertSame($firstCategory->getKey(), $categoryAfter);
        $this->assertSame($categoryBefore, $categoryAfter);
        $this->assertGreaterThan($no_of_audits_before, $no_of_audits_after);
    }

    /**
     * @test
     *
     * @return void
     */
    public function it_will_audit_sync_when_skipping_empty_values()
    {
        $this->app['config']->set('audit.empty_values', false);

        $firstCategory = factory(Category::class)->create();
        $secondCategory = factory(Category::class)->create();
        $article = factory(Article::class)->create();

        $article->categories()->attach($firstCategory);

        $no_of_audits_before = Audit::where('auditable_type', Article::class)->count();
        $categoryBefore = $article->categories()->first()->getKey();

        $article->auditSync('categories', [$secondCategory->getKey()]);

        $no_of_audits_after = Audit::where('auditable_type', Article::class)->count();
        $categoryAfter = $article->categories()->first()->getKey();

        $this->assertSame($firstCategory->getKey(), $categoryBefore);
        $this->assertSame($secondCategory->getKey(), $categoryAfter);
        $this->assertNotSame($categoryBefore, $categoryAfter);
        $this->assertGreaterThan($no_of_audits_before, $no_of_audits_after);
    }

    /**
     * @test
     *
     * @return void
     */
    public function it_will_not_audit_sync_when_skipping_empty_values_and_no_changes_made()
    {
        $this->app['config']->set('audit.empty_values', false);

        $firstCategory = factory(Category::class)->create();
        $article = factory(Article::class)->create();

        $article->categories()->attach($firstCategory);

        $no_of_audits_before = Audit::where('auditable_type', Article::class)->count();
        $categoryBefore = $article->categories()->first()->getKey();

        $article->auditSync('categories', [$firstCategory->getKey()]);

        $no_of_audits_after = Audit::where('auditable_type', Article::class)->count();
        $categoryAfter = $article->categories()->first()->getKey();

        $this->assertSame($firstCategory->getKey(), $categoryBefore);
        $this->assertSame($firstCategory->getKey(), $categoryAfter);
        $this->assertSame($categoryBefore, $categoryAfter);
        $this->assertSame($no_of_audits_before, $no_of_audits_after);
    }

    /**
     * @test
     *
     * @return void
     */
    public function it_will_not_audit_attach_when_skipping_empty_values_and_no_changes_made()
    {
        $this->app['config']->set('audit.empty_values', false);

        $firstCategory = factory(Category::class)->create();
        $article = factory(Article::class)->create();

        $article->categories()->attach($firstCategory);

        $no_of_audits_before = Audit::where('auditable_type', Article::class)->count();
        $categoryBefore = $article->categories()->first()->getKey();

        $article->auditAttach('categories', [$firstCategory->getKey()]);

        $no_of_audits_after = Audit::where('auditable_type', Article::class)->count();
        $categoryAfter = $article->categories()->first()->getKey();

        $this->assertSame($firstCategory->getKey(), $categoryBefore);
        $this->assertSame($firstCategory->getKey(), $categoryAfter);
        $this->assertSame($categoryBefore, $categoryAfter);
        $this->assertSame($no_of_audits_before, $no_of_audits_after);
    }

    /**
     * @test
     *
     * @return void
     */
    public function it_will_not_audit_detach_when_skipping_empty_values_and_no_changes_made()
    {
        $this->app['config']->set('audit.empty_values', false);

        $firstCategory = factory(Category::class)->create();
        $secondCategory = factory(Category::class)->create();
        $article = factory(Article::class)->create();

        $article->categories()->attach($firstCategory);

        $no_of_audits_before = Audit::where('auditable_type', Article::class)->count();
        $categoryBefore = $article->categories()->first()->getKey();

        $article->auditDetach('categories', [$secondCategory->getKey()]);

        $no_of_audits_after = Audit::where('auditable_type', Article::class)->count();
        $categoryAfter = $article->categories()->first()->getKey();

        $this->assertSame($firstCategory->getKey(), $categoryBefore);
        $this->assertSame($firstCategory->getKey(), $categoryAfter);
        $this->assertSame($categoryBefore, $categoryAfter);
        $this->assertSame($no_of_audits_before, $no_of_audits_after);
    }

    /**
     * @test
     *
     * @return void
     */
    public function can_audit_any_custom_event()
    {
        $article = factory(Article::class)->create();
        $article->auditEvent = 'whateverYouWant';
        $article->isCustomEvent = true;
        $article->auditCustomOld = [
            'customExample' => 'Anakin Skywalker',
        ];
        $article->auditCustomNew = [
            'customExample' => 'Darth Vader',
        ];
        Event::dispatch(new AuditCustom($article));

        $this->assertDatabaseHas(config('audit.drivers.database.table', 'audits'), [
            'auditable_id' => $article->id,
            'auditable_type' => Article::class,
            'event' => 'whateverYouWant',
            'new_values' => '{"customExample":"Darth Vader"}',
            'old_values' => '{"customExample":"Anakin Skywalker"}',
        ]);
    }

    /**
     * @test
     *
     * @return void
     */
    public function can_audit_custom_audit_model_implementation()
    {
        $audit = null;
        Event::listen(Audited::class, function ($event) use (&$audit) {
            $audit = $event->audit;
        });

        $article = new ArticleCustomAuditMorph;
        $article->title = $this->faker->unique()->sentence;
        $article->content = $this->faker->unique()->paragraph(6);
        $article->reviewed = 0;
        $article->save();

        $this->assertNotEmpty($audit);
        $this->assertSame(get_class($audit), \OwenIt\Auditing\Tests\Models\CustomAudit::class);
    }
}
