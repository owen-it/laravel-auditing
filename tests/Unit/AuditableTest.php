<?php

namespace OwenIt\Auditing\Tests\Unit;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Illuminate\Testing\Assert;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Encoders\Base64Encoder;
use OwenIt\Auditing\Exceptions\AuditableTransitionException;
use OwenIt\Auditing\Exceptions\AuditingException;
use OwenIt\Auditing\Models\Audit;
use OwenIt\Auditing\Redactors\LeftRedactor;
use OwenIt\Auditing\Redactors\RightRedactor;
use OwenIt\Auditing\Resolvers\UrlResolver;
use OwenIt\Auditing\Tests\AuditingTestCase;
use OwenIt\Auditing\Tests\Models\ApiModel;
use OwenIt\Auditing\Tests\Models\Article;
use OwenIt\Auditing\Tests\Models\ArticleExcludes;
use OwenIt\Auditing\Tests\Models\User;
use ReflectionClass;

class AuditableTest extends AuditingTestCase
{
    use WithFaker;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Clear morph maps
        Relation::morphMap([], false);
    }

    /**
     * @group Auditable::withoutAuditing
     *
     * @test
     */
    public function it_will_run_callback_with_model_auditing_disabled()
    {
        $this->assertFalse(Article::$auditingDisabled);

        $result = Article::withoutAuditing(function () {
            $this->assertTrue(Article::isAuditingDisabled());
            $this->assertFalse(ApiModel::isAuditingDisabled());

            return 'result';
        });

        $this->assertFalse(Article::$auditingDisabled);
        $this->assertSame('result', $result);
    }

    /**
     * @group Auditable::withoutAuditing
     *
     * @test
     */
    public function it_will_run_callback_with_auditing_disabled()
    {
        $this->assertFalse(Article::$auditingDisabled);

        $result = Article::withoutAuditing(function () {
            $this->assertTrue(Article::isAuditingDisabled());
            $this->assertTrue(ApiModel::isAuditingDisabled());

            return 'result';
        }, true);

        $this->assertFalse(Article::$auditingDisabled);
        $this->assertSame('result', $result);
    }

    /**
     * @group Auditable::withoutAuditing
     *
     * @test
     */
    public function it_will_run_callback_then_restore_auditing_disabled()
    {
        Article::$auditingDisabled = true;

        Article::withoutAuditing(function () {
            $this->assertTrue(Article::$auditingDisabled);
        });

        $this->assertTrue(Article::$auditingDisabled);
    }

    /**
     * @group Auditable::isAuditingEnabled
     *
     * @test
     */
    public function it_will_not_audit_models_when_running_from_the_console()
    {
        $this->app['config']->set('audit.console', false);

        $this->assertFalse(Article::isAuditingEnabled());
    }

    /**
     * @group Auditable::isAuditingEnabled
     *
     * @test
     */
    public function it_will_audit_models_when_running_from_the_console()
    {
        $this->app['config']->set('audit.console', true);

        $this->assertTrue(Article::isAuditingEnabled());
    }

    /**
     * @group Auditable::isAuditingEnabled
     *
     * @test
     */
    public function it_will_always_audit_models_when_not_running_from_the_console()
    {
        App::shouldReceive('runningInConsole')
            ->andReturn(false);

        $this->app['config']->set('audit.console', false);

        $this->assertTrue(Article::isAuditingEnabled());
    }

    /**
     * @group Auditable::bootAuditable
     *
     * @test
     */
    public function it_will_boot_trait_when_static_flag_is_set()
    {
        App::spy();

        Article::$auditingDisabled = true;

        $article = new Article;

        $this->assertFalse($article->readyForAuditing());
        App::shouldReceive('runningInConsole');

        Article::$auditingDisabled = false;
    }

    /**
     * @group Auditable::getAuditEvent
     *
     * @test
     */
    public function it_returns_null_when_the_audit_event_is_not_set()
    {
        $model = new Article;

        $this->assertNull($model->getAuditEvent());
    }

    /**
     * @group Auditable::getAuditEvent
     *
     * @test
     */
    public function it_returns_the_audit_event_that_has_been_set()
    {
        $model = new Article;
        $model->setAuditEvent('created');

        $this->assertSame('created', $model->getAuditEvent());
    }

    /**
     * @group Auditable::getAuditEvents
     *
     * @test
     */
    public function it_returns_the_default_audit_events()
    {
        $model = new Article;

        Assert::assertArraySubset([
            'created',
            'updated',
            'deleted',
            'restored',
        ], $model->getAuditEvents(), true);
    }

    /**
     * @group Auditable::getAuditEvents
     *
     * @test
     */
    public function it_returns_the_custom_audit_events_from_attribute()
    {
        $model = new Article;

        $model->auditEvents = [
            'published' => 'getPublishedEventAttributes',
            'archived',
        ];

        Assert::assertArraySubset([
            'published' => 'getPublishedEventAttributes',
            'archived',
        ], $model->getAuditEvents(), true);
    }

    /**
     * @group Auditable::getAuditEvents
     *
     * @test
     */
    public function it_returns_the_custom_audit_events_from_config()
    {
        $this->app['config']->set('audit.events', [
            'published' => 'getPublishedEventAttributes',
            'archived',
        ]);

        $model = new Article;

        Assert::assertArraySubset([
            'published' => 'getPublishedEventAttributes',
            'archived',
        ], $model->getAuditEvents(), true);
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::readyForAuditing
     *
     * @test
     */
    public function it_is_not_ready_for_auditing_with_custom_event()
    {
        $model = new Article;

        $model->setAuditEvent('published');
        $this->assertFalse($model->readyForAuditing());
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::readyForAuditing
     *
     * @test
     */
    public function it_is_ready_for_auditing_with_custom_events()
    {
        $model = new Article;

        $model->auditEvents = [
            'published' => 'getPublishedEventAttributes',
            '*ted' => 'getMultiEventAttributes',
            'archived',
        ];

        $model->setAuditEvent('published');
        $this->assertTrue($model->readyForAuditing());

        $model->setAuditEvent('archived');
        $this->assertTrue($model->readyForAuditing());

        $model->setAuditEvent('redacted');
        $this->assertTrue($model->readyForAuditing());
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::readyForAuditing
     *
     * @test
     */
    public function it_is_ready_for_auditing_with_regular_events()
    {
        $model = new Article;

        $model->setAuditEvent('created');
        $this->assertTrue($model->readyForAuditing());

        $model->setAuditEvent('updated');
        $this->assertTrue($model->readyForAuditing());

        $model->setAuditEvent('deleted');
        $this->assertTrue($model->readyForAuditing());

        $model->setAuditEvent('restored');
        $this->assertTrue($model->readyForAuditing());
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     *
     * @test
     */
    public function it_fails_when_an_invalid_audit_event_is_set()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('A valid audit event has not been set');

        $model = new Article;

        $model->setAuditEvent('published');

        $model->toAudit();
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     *
     * @test
     *
     * @dataProvider auditCustomAttributeGetterFailTestProvider
     */
    public function it_fails_when_the_custom_attribute_getters_are_missing(
        string $event,
        array $auditEvents,
        string $exceptionMessage
    ) {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $model = new Article;

        $model->auditEvents = $auditEvents;

        $model->setAuditEvent($event);

        $model->toAudit();
    }

    public static function auditCustomAttributeGetterFailTestProvider(): array
    {
        return [
            [
                'published',
                [
                    'published' => 'getPublishedEventAttributes',
                ],
                'Unable to handle "published" event, getPublishedEventAttributes() method missing',
            ],
            [
                'archived',
                [
                    'archived',
                ],
                'Unable to handle "archived" event, getArchivedEventAttributes() method missing',
            ],
            [
                'redacted',
                [
                    '*ed',
                ],
                'Unable to handle "redacted" event, getRedactedEventAttributes() method missing',
            ],
            [
                'redacted',
                [
                    '*ed' => 'getMultiEventAttributes',
                ],
                'Unable to handle "redacted" event, getMultiEventAttributes() method missing',
            ],
        ];
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     *
     * @test
     */
    public function it_fails_when_the_ip_address_resolver_implementation_is_invalid()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('Invalid Resolver implementation for: ip_address');

        $this->app['config']->set('audit.resolvers.ip_address', Audit::class);

        $model = new Article;

        $model->setAuditEvent('created');

        $model->toAudit();
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     *
     * @test
     */
    public function it_fails_when_the_url_resolver_implementation_is_invalid()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('Invalid Resolver implementation for: url');

        $this->app['config']->set('audit.resolvers.url', Audit::class);

        $model = new Article;

        $model->setAuditEvent('created');

        $model->toAudit();
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     *
     * @test
     */
    public function it_fails_when_the_user_agent_resolver_implementation_is_invalid()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('Invalid Resolver implementation for: user_agent');

        $this->app['config']->set('audit.resolvers.user_agent', Audit::class);

        $model = new Article;

        $model->setAuditEvent('created');

        $model->toAudit();
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     *
     * @test
     */
    public function it_fails_when_the_user_resolver_implementation_is_invalid()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('Invalid UserResolver implementation');

        $this->app['config']->set('audit.user.resolver', null);

        $model = new Article;

        $model->setAuditEvent('created');

        $model->toAudit();
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     *
     * @test
     */
    public function it_returns_the_audit_data()
    {
        $now = Carbon::now();

        $model = Article::factory()->make([
            'title' => 'How To Audit Eloquent Models',
            'content' => 'First step: install the laravel-auditing package.',
            'reviewed' => 1,
            'published_at' => $now,
        ]);

        $model->setAuditEvent('created');

        $this->assertCount(11, $auditData = $model->toAudit());

        $morphPrefix = config('audit.user.morph_prefix', 'user');
        Assert::assertArraySubset([
            'old_values' => [],
            'new_values' => [
                'title' => 'How To Audit Eloquent Models',
                'content' => Article::contentMutate('First step: install the laravel-auditing package.'),
                'reviewed' => 1,
                'published_at' => $now->toDateTimeString(),
            ],
            'event' => 'created',
            'auditable_id' => null,
            'auditable_type' => Article::class,
            $morphPrefix.'_id' => null,
            $morphPrefix.'_type' => null,
            'url' => UrlResolver::resolveCommandLine(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Symfony',
            'tags' => null,
        ], $auditData, true);
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     *
     * @test
     *
     * @dataProvider userResolverProvider
     */
    public function it_returns_the_audit_data_including_user_attributes(
        string $guard,
        string $driver,
        ?int $id = null,
        ?string $type = null
    ) {
        $this->app['config']->set('audit.user.guards', [
            $guard,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user, $driver);

        $now = Carbon::now();

        $model = Article::factory()->make([
            'title' => 'How To Audit Eloquent Models',
            'content' => 'First step: install the laravel-auditing package.',
            'reviewed' => 1,
            'published_at' => $now,
        ]);

        $model->setAuditEvent('created');

        $this->assertCount(11, $auditData = $model->toAudit());

        $morphPrefix = config('audit.user.morph_prefix', 'user');
        Assert::assertArraySubset([
            'old_values' => [],
            'new_values' => [
                'title' => 'How To Audit Eloquent Models',
                'content' => Article::contentMutate('First step: install the laravel-auditing package.'),
                'reviewed' => 1,
                'published_at' => $now->toDateTimeString(),
            ],
            'event' => 'created',
            'auditable_id' => null,
            'auditable_type' => Article::class,
            $morphPrefix.'_id' => $id,
            $morphPrefix.'_type' => $type,
            'url' => UrlResolver::resolveCommandLine(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Symfony',
            'tags' => null,
        ], $auditData, true);
    }

    public static function userResolverProvider(): array
    {
        return [
            [
                'api',
                'web',
                null,
                null,
            ],
            [
                'web',
                'api',
                null,
                null,
            ],
            [
                'api',
                'api',
                1,
                User::class,
            ],
            [
                'web',
                'web',
                1,
                User::class,
            ],
        ];
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     *
     * @test
     */
    public function it_excludes_attributes_from_the_audit_data_when_in_strict_mode()
    {
        $this->app['config']->set('audit.strict', true);

        $model = Article::factory()->make([
            'title' => 'How To Audit Eloquent Models',
            'content' => 'First step: install the laravel-auditing package.',
            'reviewed' => 1,
            'published_at' => Carbon::now(),
        ]);

        $model->setHidden([
            'reviewed',
        ]);

        $model->setVisible([
            'title',
            'content',
        ]);

        $model->setAuditEvent('created');

        $this->assertCount(11, $auditData = $model->toAudit());

        $morphPrefix = config('audit.user.morph_prefix', 'user');
        Assert::assertArraySubset([
            'old_values' => [],
            'new_values' => [
                'title' => 'How To Audit Eloquent Models',
                'content' => Article::contentMutate('First step: install the laravel-auditing package.'),
            ],
            'event' => 'created',
            'auditable_id' => null,
            'auditable_type' => Article::class,
            $morphPrefix.'_id' => null,
            $morphPrefix.'_type' => null,
            'url' => UrlResolver::resolveCommandLine(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Symfony',
            'tags' => null,
        ], $auditData, true);
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     *
     * @test
     */
    public function it_fails_when_the_attribute_modifier_implementation_is_invalid()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('Invalid AttributeModifier implementation: invalidAttributeRedactorOrEncoder');

        $model = Article::factory()->make();

        $model->attributeModifiers = [
            'title' => 'invalidAttributeRedactorOrEncoder',
        ];

        $model->setAuditEvent('created');

        $model->toAudit();
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     *
     * @test
     */
    public function it_modifies_the_audit_attributes_successfully()
    {
        $model = Article::factory()->make([
            'title' => 'How To Audit Models',
            'content' => 'N/A',
            'reviewed' => 0,
            'published_at' => null,
        ]);

        $now = Carbon::now();

        $model->syncOriginal();

        $model->title = 'How To Audit Eloquent Models';
        $model->content = 'First step: install the laravel-auditing package.';
        $model->reviewed = 1;
        $model->published_at = $now;

        $model->setAuditEvent('updated');

        $model->attributeModifiers = [
            'title' => RightRedactor::class,
            'content' => LeftRedactor::class,
            'reviewed' => Base64Encoder::class,
        ];

        Assert::assertArraySubset([
            'old_values' => [
                'title' => 'Ho#################',
                'content' => '##A',
                'published_at' => null,
                'reviewed' => 'MA==',
            ],
            'new_values' => [
                'title' => 'How#########################',
                'content' => '############################################kage.',
                'published_at' => $now->toDateTimeString(),
                'reviewed' => 'MQ==',
            ],
        ], $model->toAudit(), true);
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::transformAudit
     * @group Auditable::toAudit
     *
     * @test
     */
    public function it_transforms_the_audit_data()
    {
        $model = new class extends Article
        {
            protected $attributes = [
                'title' => 'How To Audit Eloquent Models',
                'content' => 'First step: install the laravel-auditing package.',
                'reviewed' => 1,
                'published_at' => '2012-06-14 15:03:00',
            ];

            public function transformAudit(array $data): array
            {
                $data['new_values']['slug'] = Str::slug($data['new_values']['title']);

                return $data;
            }
        };

        $model->setAuditEvent('created');

        $this->assertCount(11, $auditData = $model->toAudit());

        Assert::assertArraySubset([
            'new_values' => [
                'title' => 'How To Audit Eloquent Models',
                'content' => 'First step: install the laravel-auditing package.',
                'reviewed' => 1,
                'published_at' => '2012-06-14 15:03:00',
                'slug' => 'how-to-audit-eloquent-models',
            ],
        ], $auditData, true);
    }

    /**
     * @group Auditable::getAuditInclude
     *
     * @test
     */
    public function it_returns_the_default_attributes_to_be_included_in_the_audit()
    {
        $model = new Article;

        Assert::assertArraySubset([], $model->getAuditInclude(), true);
    }

    /**
     * @group Auditable::getAuditInclude
     *
     * @test
     */
    public function it_returns_the_custom_attributes_to_be_included_in_the_audit()
    {
        $model = new Article;

        $model->auditInclude = [
            'title',
            'content',
        ];

        Assert::assertArraySubset([
            'title',
            'content',
        ], $model->getAuditInclude(), true);
    }

    /**
     * @group Auditable::getAuditExclude
     *
     * @test
     */
    public function it_returns_the_default_attributes_to_be_excluded_from_the_audit()
    {
        $model = new Article;

        Assert::assertArraySubset([], $model->getAuditExclude(), true);
    }

    /**
     * @group Auditable::getAuditExclude
     *
     * @test
     */
    public function it_returns_the_custom_attributes_to_be_excluded_from_the_audit()
    {
        $model = new Article;

        $model->auditExclude = [
            'published_at',
        ];

        Assert::assertArraySubset([
            'published_at',
        ], $model->getAuditExclude(), true);
    }

    /**
     * @test
     *
     * @return void
     */
    public function it_excludes_attributes_from_exclude()
    {
        $model = new ArticleExcludes;

        $model->title = 'Darth Vader announces new paternity leave option for stormtroopers';
        $model->content = 'Storm troopers has for a long time wanted a more flexible schedule... ';
        $model->reviewed = 1;
        $model->save();

        $audit = Audit::first();

        $this->assertNotNull($audit);

        $this->assertArrayNotHasKey('title', $audit->getModified());
    }

    /**
     * @group Auditable::getAuditStrict
     *
     * @test
     */
    public function it_returns_the_default_audit_strict_value()
    {
        $model = new Article;

        $this->assertFalse($model->getAuditStrict());
    }

    /**
     * @group Auditable::getAuditStrict
     *
     * @test
     */
    public function it_returns_the_custom_audit_strict_value_from_attribute()
    {
        $model = new Article;

        $model->auditStrict = true;

        $this->assertTrue($model->getAuditStrict());
    }

    /**
     * @group Auditable::getAuditStrict
     *
     * @test
     */
    public function it_returns_the_custom_audit_strict_value_from_config()
    {
        $this->app['config']->set('audit.strict', true);

        $model = new Article;

        $this->assertTrue($model->getAuditStrict());
    }

    /**
     * @group Auditable::getAuditTimestamps
     *
     * @test
     */
    public function it_returns_the_default_audit_timestamps_value()
    {
        $model = new Article;

        $this->assertFalse($model->getAuditTimestamps());
    }

    /**
     * @group Auditable::getAuditTimestamps
     *
     * @test
     */
    public function it_returns_the_custom_audit_timestamps_value_from_attribute()
    {
        $model = new Article;

        $model->auditTimestamps = true;

        $this->assertTrue($model->getAuditTimestamps());
    }

    /**
     * @group Auditable::getAuditTimestamps
     *
     * @test
     */
    public function it_returns_the_custom_audit_timestamps_value_from_config()
    {
        $this->app['config']->set('audit.timestamps', true);

        $model = new Article;

        $this->assertTrue($model->getAuditTimestamps());
    }

    /**
     * @group Auditable::getAuditDriver
     *
     * @test
     */
    public function it_returns_the_default_audit_driver_value()
    {
        $model = new Article;

        $this->assertSame('database', $model->getAuditDriver());
    }

    /**
     * @group Auditable::getAuditDriver
     *
     * @test
     */
    public function it_returns_the_custom_audit_driver_value_from_attribute()
    {
        $model = new Article;

        $model->auditDriver = 'RedisDriver';

        $this->assertSame('RedisDriver', $model->getAuditDriver());
    }

    /**
     * @group Auditable::getAuditDriver
     *
     * @test
     */
    public function it_returns_the_custom_audit_driver_value_from_config()
    {
        $this->app['config']->set('audit.driver', 'RedisDriver');

        $model = new Article;

        $this->assertSame('RedisDriver', $model->getAuditDriver());
    }

    /**
     * @group Auditable::getAuditThreshold
     *
     * @test
     */
    public function it_returns_the_default_audit_threshold_value()
    {
        $model = new Article;

        $this->assertSame(0, $model->getAuditThreshold());
    }

    /**
     * @group Auditable::getAuditThreshold
     *
     * @test
     */
    public function it_returns_the_custom_audit_threshold_value_from_attribute()
    {
        $model = new Article;

        $model->auditThreshold = 10;

        $this->assertSame(10, $model->getAuditThreshold());
    }

    /**
     * @group Auditable::getAuditThreshold
     *
     * @test
     */
    public function it_returns_the_custom_audit_threshold_value_from_config()
    {
        $this->app['config']->set('audit.threshold', 200);

        $model = new Article;

        $this->assertSame(200, $model->getAuditThreshold());
    }

    /**
     * @group Auditable::generateTags
     *
     * @test
     */
    public function it_returns_the_default_generated_audit_tags()
    {
        $model = new Article;

        Assert::assertArraySubset([], $model->generateTags(), true);
    }

    /**
     * @group Auditable::generateTags
     *
     * @test
     */
    public function it_returns_the_custom_generated_audit_tags()
    {
        $model = new class extends Article
        {
            public function generateTags(): array
            {
                return [
                    'foo',
                    'bar',
                ];
            }
        };

        Assert::assertArraySubset([
            'foo',
            'bar',
        ], $model->generateTags(), true);
    }

    /**
     * @group Auditable::transitionTo
     *
     * @test
     */
    public function it_fails_to_transition_when_the_audit_auditable_type_does_not_match_the_model_type()
    {
        $this->expectException(AuditableTransitionException::class);
        $this->expectExceptionMessage('Expected Auditable type OwenIt\Auditing\Tests\Models\Article, got OwenIt\Auditing\Tests\Models\User instead');

        $audit = new Audit([
            'auditable_type' => User::class,
        ]);

        $model = new Article;

        $model->transitionTo($audit);
    }

    /**
     * @group Auditable::transitionTo
     *
     * @test
     */
    public function it_works_on_times_restored_correctly()
    {
        config(['app.timezone' => 'America/New_York']);
        date_default_timezone_set('America/New_York');

        $originalStart = new Carbon('2022-01-01 12:00:00');

        $article = Article::factory()->create([
            'title' => 'How To Audit Eloquent Models',
            'content' => 'First step: install the laravel-auditing package.',
            'reviewed' => 1,
            'published_at' => $originalStart,
        ]);

        $model = Article::first();

        $this->assertNotNull($model);

        $this->assertEquals($model->published_at, $originalStart);

        $model->published_at = new Carbon('2022-01-01 12:30:00');
        $model->save();
        $audit = $model->audits->last();
        $audit->auditable_id = $model->id;

        $model->transitionTo($audit, true);

        $this->assertEquals($model->published_at, $originalStart);
    }

    /**
     * @group Auditable::transitionTo
     *
     * @test
     */
    public function it_fails_to_transition_when_the_audit_auditable_type_does_not_match_the_morph_map_value()
    {
        $this->expectException(AuditableTransitionException::class);
        $this->expectExceptionMessage('Expected Auditable type articles, got users instead');

        Relation::morphMap([
            'articles' => Article::class,
        ]);

        $audit = new Audit([
            'auditable_type' => 'users',
        ]);

        $model = new Article;

        $model->transitionTo($audit);
    }

    /**
     * @group Auditable::transitionTo
     *
     * @test
     */
    public function it_fails_to_transition_when_the_audit_auditable_id_does_not_match_the_model_id()
    {
        $this->expectException(AuditableTransitionException::class);
        $this->expectExceptionMessage('Expected Auditable id (integer)2, got (integer)1 instead');

        $firstModel = Article::factory()->create();
        $firstAudit = $firstModel->audits()->first();
        $this->assertNotNull($firstAudit);

        $firstAudit->auditable_id = $firstModel->id;

        $secondModel = Article::factory()->create();

        $secondModel->transitionTo($firstAudit);
    }

    /**
     * @group Auditable::transitionTo
     *
     * @test
     */
    public function it_fails_to_transition_when_the_audit_auditable_id_type_does_not_match_the_model_id_type()
    {
        $this->expectException(AuditableTransitionException::class);
        $this->expectExceptionMessage('Expected Auditable id (integer)1, got (string)1 instead');

        $model = Article::factory()->create();

        $audit = Audit::create([
            'event' => 'updated',
            'auditable_type' => Article::class,
            'auditable_id' => (string) $model->id,
        ]);

        // Make sure the auditable_id isn't being cast
        $auditReflection = new ReflectionClass($audit);

        $auditCastsProperty = $auditReflection->getProperty('casts');
        $auditCastsProperty->setAccessible(true);
        $auditCastsProperty->setValue($audit, [
            'old_values' => 'json',
            'new_values' => 'json',
        ]);

        $model->transitionTo($audit);
    }

    /**
     * @group Auditable::transitionTo
     *
     * @test
     */
    public function it_transitions_when_the_audit_auditable_id_type_does_not_match_the_model_id_type()
    {
        $model = Article::factory()->create();

        // Key depends on type
        if ($model->getKeyType() == 'string') {
            $key = (string) $model->id;
        } else {
            $key = (int) $model->id;
        }

        $audit = Audit::create([
            'event' => 'updated',
            'auditable_type' => Article::class,
            'auditable_id' => $key,
        ]);

        $this->assertInstanceOf(Auditable::class, $model->transitionTo($audit));
    }

    /**
     * @group Auditable::transitionTo
     *
     * @test
     */
    public function it_fails_to_transition_when_an_attribute_redactor_is_set()
    {
        $this->expectException(AuditableTransitionException::class);
        $this->expectExceptionMessage('Cannot transition states when an AttributeRedactor is set');

        $model = Article::factory()->create();

        $model->attributeModifiers = [
            'title' => RightRedactor::class,
        ];

        $audit = Audit::create([
            'event' => 'created',
            'auditable_id' => $model->getKey(),
            'auditable_type' => Article::class,
        ]);

        $model->transitionTo($audit);
    }

    /**
     * @group Auditable::transitionTo
     *
     * @test
     */
    public function it_fails_to_transition_when_the_auditable_attribute_compatibility_is_not_met()
    {
        $model = Article::factory()->create();

        $incompatibleAudit = Audit::create([
            'event' => 'created',
            'auditable_id' => $model->getKey(),
            'auditable_type' => Article::class,
            'old_values' => [],
            'new_values' => [
                'subject' => 'Culpa qui rerum excepturi quisquam quia officiis.',
                'text' => 'Magnam enim suscipit officiis tempore ut quis harum.',
            ],
        ]);

        $exceptionWasThrown = false;

        try {
            $model->transitionTo($incompatibleAudit);
        } catch (AuditableTransitionException $e) {
            $this->assertSame(
                'Incompatibility between [OwenIt\Auditing\Tests\Models\Article:1] and [OwenIt\Auditing\Models\Audit:2]',
                $e->getMessage()
            );

            Assert::assertArraySubset([
                'subject',
                'text',
            ], $e->getIncompatibilities(), true);

            $exceptionWasThrown = true;
        }

        $this->assertTrue($exceptionWasThrown);
    }

    /**
     * @group Auditable::transitionTo
     *
     * @test
     *
     * @dataProvider auditableTransitionTestProvider
     */
    public function it_transitions_to_another_model_state(
        bool $morphMap,
        array $oldValues,
        array $newValues,
        array $oldExpectation,
        array $newExpectation
    ) {
        $models = Article::factory()->count(2)->create([
            'title' => 'Facilis voluptas qui impedit deserunt vitae quidem.',
            'content' => 'Consectetur distinctio nihil eveniet cum. Expedita dolores animi dolorum eos repellat rerum.',
        ]);

        if ($morphMap) {
            Relation::morphMap([
                'articles' => Article::class,
            ]);
        }

        $auditableType = $morphMap ? 'articles' : Article::class;

        $audits = $models->map(function (Article $model) use ($auditableType, $oldValues, $newValues) {
            return Audit::create([
                'event' => 'updated',
                'auditable_id' => $model->getKey(),
                'auditable_type' => $auditableType,
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ]);
        });

        // Transition with old values
        $this->assertInstanceOf(Auditable::class, $models[0]->transitionTo($audits[0], true));
        $this->assertSame($oldExpectation, $models[0]->getDirty());

        // Transition with new values
        $this->assertInstanceOf(Auditable::class, $models[1]->transitionTo($audits[1]));
        $this->assertSame($newExpectation, $models[1]->getDirty());
    }

    /**
     * @test
     */
    public function it_works_with_string_key_models()
    {
        $model = ApiModel::factory()->create();
        $model->save();
        $model->refresh();

        $this->assertCount(1, $model->audits);

        $model->content = 'Something else';
        $model->save();
        $model->refresh();

        $this->assertCount(2, $model->audits);
    }

    public static function auditableTransitionTestProvider(): array
    {
        return [
            //
            // Audit data and expectations for retrieved event
            //
            [
                // Morph Map
                false,

                // Old values
                [],

                // New values
                [],

                // Expectation when transitioning with old values
                [],

                // Expectation when transitioning with new values
                [],
            ],

            //
            // Audit data and expectations for created/restored event
            //
            [
                // Morph Map
                true,

                // Old values
                [],

                // New values
                [
                    'title' => 'Nullam egestas interdum eleifend.',
                    'content' => 'Morbi consectetur laoreet sem, eu tempus odio tempor id.',
                ],

                // Expectation when transitioning with old values
                [],

                // Expectation when transitioning with new values
                [
                    'title' => 'NULLAM EGESTAS INTERDUM ELEIFEND.',
                    'content' => Article::contentMutate('Morbi consectetur laoreet sem, eu tempus odio tempor id.'),
                ],
            ],

            //
            // Audit data and expectations for updated event
            //
            [
                // Morph Map
                false,

                // Old values
                [
                    'title' => 'Vivamus a urna et lorem faucibus malesuada nec nec magna.',
                    'content' => 'Mauris ipsum erat, semper non quam vel, sodales tincidunt ligula.',
                ],

                // New values
                [
                    'title' => 'Nullam egestas interdum eleifend.',
                    'content' => 'Morbi consectetur laoreet sem, eu tempus odio tempor id.',
                ],

                // Expectation when transitioning with old values
                [
                    'title' => 'VIVAMUS A URNA ET LOREM FAUCIBUS MALESUADA NEC NEC MAGNA.',
                    'content' => Article::contentMutate('Mauris ipsum erat, semper non quam vel, sodales tincidunt ligula.'),
                ],

                // Expectation when transitioning with new values
                [
                    'title' => 'NULLAM EGESTAS INTERDUM ELEIFEND.',
                    'content' => Article::contentMutate('Morbi consectetur laoreet sem, eu tempus odio tempor id.'),
                ],
            ],

            //
            // Audit data and expectations for deleted event
            //
            [
                // Morph Map
                true,

                // Old values
                [
                    'title' => 'Vivamus a urna et lorem faucibus malesuada nec nec magna.',
                    'content' => 'Mauris ipsum erat, semper non quam vel, sodales tincidunt ligula.',
                ],

                // New values
                [],

                // Expectation when transitioning with old values
                [
                    'title' => 'VIVAMUS A URNA ET LOREM FAUCIBUS MALESUADA NEC NEC MAGNA.',
                    'content' => Article::contentMutate('Mauris ipsum erat, semper non quam vel, sodales tincidunt ligula.'),
                ],

                // Expectation when transitioning with new values
                [],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_works_when_config_allowed_array_value_is_true()
    {
        $this->app['config']->set('audit.allowed_array_values', true);

        $model = Article::factory()->make([
            'title' => 'How To Audit Eloquent Models',
            'content' => 'First step: install the laravel-auditing package.',
            'reviewed' => 1,
            'images' => [
                'https://example.com/image1.jpg',
                'https://example.com/image2.jpg',
            ],
        ]);

        $model->setAuditEvent('created');

        $auditData = $model->toAudit();

        $morphPrefix = config('audit.user.morph_prefix', 'user');
        Assert::assertArraySubset([
            'old_values' => [],
            'new_values' => [
                'title' => 'How To Audit Eloquent Models',
                'content' => Article::contentMutate('First step: install the laravel-auditing package.'),
                'reviewed' => 1,
                'images' => [
                    'https://example.com/image1.jpg',
                    'https://example.com/image2.jpg',
                ],
            ],
            'event' => 'created',
            'auditable_id' => null,
            'auditable_type' => Article::class,
            $morphPrefix.'_id' => null,
            $morphPrefix.'_type' => null,
            'url' => UrlResolver::resolveCommandLine(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Symfony',
            'tags' => null,
        ], $auditData, true);
    }

    /**
     * @test
     */
    public function it_works_when_config_allowed_array_value_is_false()
    {
        $this->app['config']->set('audit.allowed_array_values', false);

        $model = Article::factory()->make([
            'title' => 'How To Audit Eloquent Models',
            'content' => 'First step: install the laravel-auditing package.',
            'reviewed' => 1,
            'images' => [
                'https://example.com/image1.jpg',
                'https://example.com/image2.jpg',
            ],
        ]);

        $model->setAuditEvent('created');

        $auditData = $model->toAudit();

        $morphPrefix = config('audit.user.morph_prefix', 'user');
        Assert::assertArraySubset([
            'old_values' => [],
            'new_values' => [
                'title' => 'How To Audit Eloquent Models',
                'content' => Article::contentMutate('First step: install the laravel-auditing package.'),
                'reviewed' => 1,
            ],
            'event' => 'created',
            'auditable_id' => null,
            'auditable_type' => Article::class,
            $morphPrefix.'_id' => null,
            $morphPrefix.'_type' => null,
            'url' => UrlResolver::resolveCommandLine(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Symfony',
            'tags' => null,
        ], $auditData, true);
    }
}
