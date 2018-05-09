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

namespace OwenIt\Auditing\Tests;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Exceptions\AuditableTransitionException;
use OwenIt\Auditing\Exceptions\AuditingException;
use OwenIt\Auditing\Models\Audit;
use OwenIt\Auditing\Redactors\LeftRedactor;
use OwenIt\Auditing\Redactors\RightRedactor;
use OwenIt\Auditing\Tests\Models\Article;
use OwenIt\Auditing\Tests\Models\User;

class AuditableTest extends AuditingTestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        // Clear morph maps
        Relation::morphMap([], false);
    }

    /**
     * @group Auditable::isAuditingEnabled
     * @test
     */
    public function itWillNotAuditModelsWhenRunningFromTheConsole()
    {
        $this->app['config']->set('audit.console', false);

        $this->assertFalse(Article::isAuditingEnabled());
    }

    /**
     * @group Auditable::isAuditingEnabled
     * @test
     */
    public function itWillAuditModelsWhenRunningFromTheConsole()
    {
        $this->app['config']->set('audit.console', true);

        $this->assertTrue(Article::isAuditingEnabled());
    }

    /**
     * @group Auditable::isAuditingEnabled
     * @test
     */
    public function itWillAlwaysAuditModelsWhenNotRunningFromTheConsole()
    {
        App::shouldReceive('runningInConsole')
            ->andReturn(false);

        $this->app['config']->set('audit.console', false);

        $this->assertTrue(Article::isAuditingEnabled());
    }

    /**
     * @group Auditable::getAuditEvent
     * @test
     */
    public function itReturnsNullWhenTheAuditEventIsNotSet()
    {
        $model = new Article();

        $this->assertNull($model->getAuditEvent());
    }

    /**
     * @group Auditable::getAuditEvent
     * @test
     */
    public function itReturnsTheAuditEventThatHasBeenSet()
    {
        $model = new Article();
        $model->setAuditEvent('created');

        $this->assertSame('created', $model->getAuditEvent());
    }

    /**
     * @group Auditable::getAuditEvents
     * @test
     */
    public function itReturnsTheDefaultAuditEvents()
    {
        $model = new Article();

        $this->assertArraySubset([
            'created',
            'updated',
            'deleted',
            'restored',
        ], $model->getAuditEvents(), true);
    }

    /**
     * @group Auditable::getAuditEvents
     * @test
     */
    public function itReturnsTheCustomAuditEventsFromAttribute()
    {
        $model = new Article();

        $model->auditEvents = [
            'published' => 'getPublishedEventAttributes',
            'archived',
        ];

        $this->assertArraySubset([
            'published' => 'getPublishedEventAttributes',
            'archived',
        ], $model->getAuditEvents(), true);
    }

    /**
     * @group Auditable::getAuditEvents
     * @test
     */
    public function itReturnsTheCustomAuditEventsFromConfig()
    {
        $this->app['config']->set('audit.events', [
            'published' => 'getPublishedEventAttributes',
            'archived',
        ]);

        $model = new Article();

        $this->assertArraySubset([
            'published' => 'getPublishedEventAttributes',
            'archived',
        ], $model->getAuditEvents(), true);
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::readyForAuditing
     * @test
     */
    public function itIsNotReadyForAuditingWithCustomEvent()
    {
        $model = new Article();

        $model->setAuditEvent('published');
        $this->assertFalse($model->readyForAuditing());
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::readyForAuditing
     * @test
     */
    public function itIsReadyForAuditingWithCustomEvents()
    {
        $model = new Article();

        $model->auditEvents = [
            'published' => 'getPublishedEventAttributes',
            '*ted'      => 'getMultiEventAttributes',
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
     * @test
     */
    public function itIsReadyForAuditingWithRegularEvents()
    {
        $model = new Article();

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
     * @test
     */
    public function itFailsWhenAnInvalidAuditEventIsSet()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('A valid audit event has not been set');

        $model = new Article();

        $model->setAuditEvent('published');

        $model->toAudit();
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     *
     * @dataProvider auditCustomAttributeGetterFailTestProvider
     *
     * @param string $event
     * @param array  $auditEvents
     * @param string $exceptionMessage
     */
    public function itFailsWhenTheCustomAttributeGettersAreMissing(
        string $event,
        array $auditEvents,
        string $exceptionMessage
    ) {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $model = new Article();

        $model->auditEvents = $auditEvents;

        $model->setAuditEvent($event);

        $model->toAudit();
    }

    /**
     * @return array
     */
    public function auditCustomAttributeGetterFailTestProvider()
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
     * @test
     */
    public function itFailsWhenTheIpAddressResolverImplementationIsInvalid()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('Invalid IpAddressResolver implementation');

        $this->app['config']->set('audit.resolver.ip_address', null);

        $model = new Article();

        $model->setAuditEvent('created');

        $model->toAudit();
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     */
    public function itFailsWhenTheUrlResolverImplementationIsInvalid()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('Invalid UrlResolver implementation');

        $this->app['config']->set('audit.resolver.url', null);

        $model = new Article();

        $model->setAuditEvent('created');

        $model->toAudit();
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     */
    public function itFailsWhenTheUserAgentResolverImplementationIsInvalid()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('Invalid UserAgentResolver implementation');

        $this->app['config']->set('audit.resolver.user_agent', null);

        $model = new Article();

        $model->setAuditEvent('created');

        $model->toAudit();
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     */
    public function itFailsWhenTheUserResolverImplementationIsInvalid()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('Invalid UserResolver implementation');

        $this->app['config']->set('audit.resolver.user', null);

        $model = new Article();

        $model->setAuditEvent('created');

        $model->toAudit();
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     */
    public function itFailsWhenTheUserIdResolverImplementationIsInvalid()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('Invalid UserIdResolver implementation');

        $this->app['config']->set('audit.resolver.user_id', null);

        $model = new Article();

        $model->setAuditEvent('created');

        $model->toAudit();
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     */
    public function itFailsWhenTheUserClassResolverImplementationIsInvalidWhenMorphing()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('Invalid UserClassResolver implementation');

        $this->app['config']->set('audit.morphable', true);
        $this->app['config']->set('audit.resolver.user_class', null);

        $model = new Article();

        $model->setAuditEvent('created');

        $model->toAudit();
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     */
    public function itReturnsTheAuditData()
    {
        $now = Carbon::now();

        $model = factory(Article::class)->make([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'First step: install the laravel-auditing package.',
            'reviewed'     => 1,
            'published_at' => $now,
        ]);

        $model->setAuditEvent('created');

        $this->assertCount(10, $auditData = $model->toAudit());

        $this->assertArraySubset([
            'old_values' => [],
            'new_values' => [
                'title'        => 'How To Audit Eloquent Models',
                'content'      => 'First step: install the laravel-auditing package.',
                'reviewed'     => 1,
                'published_at' => $now->format('Y-m-d H:i:s'),
            ],
            'event'          => 'created',
            'auditable_id'   => null,
            'auditable_type' => Article::class,
            'user_id'        => null,
            'url'            => 'console',
            'ip_address'     => '127.0.0.1',
            'user_agent'     => 'Symfony/3.X',
            'tags'           => null,
        ], $auditData, true);
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     */
    public function itReturnsTheAuditDataIncludingUserAttributes()
    {
        $user = factory(User::class)->create();

        $this->actingAs($user);

        $now = Carbon::now();

        $model = factory(Article::class)->make([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'First step: install the laravel-auditing package.',
            'reviewed'     => 1,
            'published_at' => $now,
        ]);

        $model->setAuditEvent('created');

        $this->assertCount(10, $auditData = $model->toAudit());

        $this->assertArraySubset([
            'old_values' => [],
            'new_values' => [
                'title'        => 'How To Audit Eloquent Models',
                'content'      => 'First step: install the laravel-auditing package.',
                'reviewed'     => 1,
                'published_at' => $now->format('Y-m-d H:i:s'),
            ],
            'event'          => 'created',
            'auditable_id'   => null,
            'auditable_type' => Article::class,
            'user_id'        => 1,
            'url'            => 'console',
            'ip_address'     => '127.0.0.1',
            'user_agent'     => 'Symfony/3.X',
            'tags'           => null,
        ], $auditData, true);
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     */
    public function itReturnsTheAuditDataIncludingUserAttributesWhenMorphing()
    {
        $this->app['config']->set('audit.morphable', true);

        $user = factory(User::class)->create();

        $this->actingAs($user);

        $now = Carbon::now();

        $model = factory(Article::class)->make([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'First step: install the laravel-auditing package.',
            'reviewed'     => 1,
            'published_at' => $now,
        ]);

        $model->setAuditEvent('created');

        $this->assertCount(11, $auditData = $model->toAudit());

        $this->assertArraySubset([
            'old_values' => [],
            'new_values' => [
                'title'        => 'How To Audit Eloquent Models',
                'content'      => 'First step: install the laravel-auditing package.',
                'reviewed'     => 1,
                'published_at' => $now->format('Y-m-d H:i:s'),
            ],
            'event'          => 'created',
            'auditable_id'   => null,
            'auditable_type' => Article::class,
            'user_id'        => 1,
            'user_type'      => 'OwenIt\Auditing\Tests\Models\User',
            'url'            => 'console',
            'ip_address'     => '127.0.0.1',
            'user_agent'     => 'Symfony/3.X',
            'tags'           => null,
        ], $auditData, true);
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     */
    public function itExcludesAttributesFromTheAuditDataWhenInStrictMode()
    {
        $this->app['config']->set('audit.strict', true);

        $model = factory(Article::class)->make([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'First step: install the laravel-auditing package.',
            'reviewed'     => 1,
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

        $this->assertCount(10, $auditData = $model->toAudit());

        $this->assertArraySubset([
            'old_values' => [],
            'new_values' => [
                'title'   => 'How To Audit Eloquent Models',
                'content' => 'First step: install the laravel-auditing package.',
            ],
            'event'          => 'created',
            'auditable_id'   => null,
            'auditable_type' => Article::class,
            'user_id'        => null,
            'url'            => 'console',
            'ip_address'     => '127.0.0.1',
            'user_agent'     => 'Symfony/3.X',
            'tags'           => null,
        ], $auditData, true);
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     */
    public function itFailsWhenTheAuditRedactorImplementationIsInvalid()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('Invalid AuditRedactor implementation');

        $this->app['config']->set('audit.redact', true);

        $model = factory(Article::class)->make();

        $model->auditRedactors = [
            'title' => 'invalidAuditRedactor',
        ];

        $model->setAuditEvent('created');

        $model->toAudit();
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     */
    public function itRedactsTheAuditData()
    {
        $this->app['config']->set('audit.redact', true);

        $model = factory(Article::class)->make([
            'title'    => 'How To Audit Models',
            'content'  => 'N/A',
            'reviewed' => 0,
        ]);

        $model->syncOriginal();

        $model->title = 'How To Audit Eloquent Models';
        $model->content = 'First step: install the laravel-auditing package.';
        $model->reviewed = 1;

        $model->setAuditEvent('updated');

        $model->auditRedactors = [
            'title'   => RightRedactor::class,
            'content' => LeftRedactor::class,
        ];

        $this->assertArraySubset([
            'old_values' => [
                'title'   => 'Ho#################',
                'content' => '##A',
            ],
            'new_values' => [
                'title'   => 'How#########################',
                'content' => '############################################kage.',
            ],
        ], $model->toAudit(), true);
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::transformAudit
     * @group Auditable::toAudit
     * @test
     */
    public function itTransformsTheAuditData()
    {
        $model = new class() extends Article {
            protected $attributes = [
                'title'        => 'How To Audit Eloquent Models',
                'content'      => 'First step: install the laravel-auditing package.',
                'reviewed'     => 1,
                'published_at' => '2012-06-14 15:03:00',
            ];

            public function transformAudit(array $data): array
            {
                $data['new_values']['slug'] = str_slug($data['new_values']['title']);

                return $data;
            }
        };

        $model->setAuditEvent('created');

        $this->assertCount(10, $auditData = $model->toAudit());

        $this->assertArraySubset([
            'new_values' => [
                'title'        => 'How To Audit Eloquent Models',
                'content'      => 'First step: install the laravel-auditing package.',
                'reviewed'     => 1,
                'published_at' => '2012-06-14 15:03:00',
                'slug'         => 'how-to-audit-eloquent-models',
            ],
        ], $auditData, true);
    }

    /**
     * @group Auditable::getAuditInclude
     * @test
     */
    public function itReturnsTheDefaultAttributesToBeIncludedInTheAudit()
    {
        $model = new Article();

        $this->assertArraySubset([], $model->getAuditInclude(), true);
    }

    /**
     * @group Auditable::getAuditInclude
     * @test
     */
    public function itReturnsTheCustomAttributesToBeIncludedInTheAudit()
    {
        $model = new Article();

        $model->auditInclude = [
            'title',
            'content',
        ];

        $this->assertArraySubset([
            'title',
            'content',
        ], $model->getAuditInclude(), true);
    }

    /**
     * @group Auditable::getAuditExclude
     * @test
     */
    public function itReturnsTheDefaultAttributesToBeExcludedFromTheAudit()
    {
        $model = new Article();

        $this->assertArraySubset([], $model->getAuditExclude(), true);
    }

    /**
     * @group Auditable::getAuditExclude
     * @test
     */
    public function itReturnsTheCustomAttributesToBeExcludedFromTheAudit()
    {
        $model = new Article();

        $model->auditExclude = [
            'published_at',
        ];

        $this->assertArraySubset([
            'published_at',
        ], $model->getAuditExclude(), true);
    }

    /**
     * @group Auditable::getAuditStrict
     * @test
     */
    public function itReturnsTheDefaultAuditStrictValue()
    {
        $model = new Article();

        $this->assertFalse($model->getAuditStrict());
    }

    /**
     * @group Auditable::getAuditStrict
     * @test
     */
    public function itReturnsTheCustomAuditStrictValueFromAttribute()
    {
        $model = new Article();

        $model->auditStrict = true;

        $this->assertTrue($model->getAuditStrict());
    }

    /**
     * @group Auditable::getAuditStrict
     * @test
     */
    public function itReturnsTheCustomAuditStrictValueFromConfig()
    {
        $this->app['config']->set('audit.strict', true);

        $model = new Article();

        $this->assertTrue($model->getAuditStrict());
    }

    /**
     * @group Auditable::getAuditTimestamps
     * @test
     */
    public function itReturnsTheDefaultAuditTimestampsValue()
    {
        $model = new Article();

        $this->assertFalse($model->getAuditTimestamps());
    }

    /**
     * @group Auditable::getAuditTimestamps
     * @test
     */
    public function itReturnsTheCustomAuditTimestampsValueFromAttribute()
    {
        $model = new Article();

        $model->auditTimestamps = true;

        $this->assertTrue($model->getAuditTimestamps());
    }

    /**
     * @group Auditable::getAuditTimestamps
     * @test
     */
    public function itReturnsTheCustomAuditTimestampsValueFromConfig()
    {
        $this->app['config']->set('audit.timestamps', true);

        $model = new Article();

        $this->assertTrue($model->getAuditTimestamps());
    }

    /**
     * @group Auditable::getAuditDriver
     * @test
     */
    public function itReturnsTheDefaultAuditDriverValue()
    {
        $model = new Article();

        $this->assertSame('database', $model->getAuditDriver());
    }

    /**
     * @group Auditable::getAuditDriver
     * @test
     */
    public function itReturnsTheCustomAuditDriverValueFromAttribute()
    {
        $model = new Article();

        $model->auditDriver = 'RedisDriver';

        $this->assertSame('RedisDriver', $model->getAuditDriver());
    }

    /**
     * @group Auditable::getAuditDriver
     * @test
     */
    public function itReturnsTheCustomAuditDriverValueFromConfig()
    {
        $this->app['config']->set('audit.driver', 'RedisDriver');

        $model = new Article();

        $this->assertSame('RedisDriver', $model->getAuditDriver());
    }

    /**
     * @group Auditable::getAuditThreshold
     * @test
     */
    public function itReturnsTheDefaultAuditThresholdValue()
    {
        $model = new Article();

        $this->assertSame(0, $model->getAuditThreshold());
    }

    /**
     * @group Auditable::getAuditThreshold
     * @test
     */
    public function itReturnsTheCustomAuditThresholdValueFromAttribute()
    {
        $model = new Article();

        $model->auditThreshold = 10;

        $this->assertSame(10, $model->getAuditThreshold());
    }

    /**
     * @group Auditable::getAuditThreshold
     * @test
     */
    public function itReturnsTheCustomAuditThresholdValueFromConfig()
    {
        $this->app['config']->set('audit.threshold', 200);

        $model = new Article();

        $this->assertSame(200, $model->getAuditThreshold());
    }

    /**
     * @group Auditable::generateTags
     * @test
     */
    public function itReturnsTheDefaultGeneratedAuditTags()
    {
        $model = new Article();

        $this->assertArraySubset([], $model->generateTags(), true);
    }

    /**
     * @group Auditable::generateTags
     * @test
     */
    public function itReturnsTheCustomGeneratedAuditTags()
    {
        $model = new class() extends Article {
            public function generateTags(): array
            {
                return [
                    'foo',
                    'bar',
                ];
            }
        };

        $this->assertArraySubset([
            'foo',
            'bar',
        ], $model->generateTags(), true);
    }

    /**
     * @group Auditable::transitionTo
     * @test
     */
    public function itFailsToTransitionWhenTheAuditAuditableTypeDoesNotMatchTheModelType()
    {
        $this->expectException(AuditableTransitionException::class);
        $this->expectExceptionMessage('Expected Auditable type OwenIt\Auditing\Tests\Models\Article, got OwenIt\Auditing\Tests\Models\User instead');

        $audit = factory(Audit::class)->make([
            'auditable_type' => User::class,
        ]);

        $model = new Article();

        $model->transitionTo($audit);
    }

    /**
     * @group Auditable::transitionTo
     * @test
     */
    public function itFailsToTransitionWhenTheAuditAuditableTypeDoesNotMatchTheMorphMapValue()
    {
        $this->expectException(AuditableTransitionException::class);
        $this->expectExceptionMessage('Expected Auditable type articles, got users instead');

        Relation::morphMap([
            'articles' => Article::class,
        ]);

        $audit = factory(Audit::class)->make([
            'auditable_type' => 'users',
        ]);

        $model = new Article();

        $model->transitionTo($audit);
    }

    /**
     * @group Auditable::transitionTo
     * @test
     */
    public function itFailsToTransitionWhenTheAuditAuditableIdDoesNotMatchTheModelId()
    {
        $this->expectException(AuditableTransitionException::class);
        $this->expectExceptionMessage('Expected Auditable id 2, got 1 instead');

        $firstAudit = factory(Article::class)->create()->audits()->first();
        $secondModel = factory(Article::class)->create();

        $secondModel->transitionTo($firstAudit);
    }

    /**
     * @group Auditable::transitionTo
     * @test
     */
    public function itFailsToTransitionWhenAuditRedactorsAreSet()
    {
        $this->expectException(AuditableTransitionException::class);
        $this->expectExceptionMessage('Cannot transition states when Audit redactors are set');

        $model = factory(Article::class)->create();

        $model->auditRedactors = [
            'title' => RightRedactor::class,
        ];

        $audit = factory(Audit::class)->create([
            'auditable_id'   => $model->getKey(),
            'auditable_type' => Article::class,
        ]);

        $model->transitionTo($audit);
    }

    /**
     * @group Auditable::transitionTo
     * @test
     */
    public function itFailsToTransitionWhenTheAuditableAttributeCompatibilityIsNotMet()
    {
        $model = factory(Article::class)->create();

        $incompatibleAudit = factory(Audit::class)->create([
            'event'          => 'created',
            'auditable_id'   => $model->getKey(),
            'auditable_type' => Article::class,
            'old_values'     => [],
            'new_values'     => [
                'subject' => 'Culpa qui rerum excepturi quisquam quia officiis.',
                'text'    => 'Magnam enim suscipit officiis tempore ut quis harum.',
            ],
        ]);

        try {
            $model->transitionTo($incompatibleAudit);
        } catch (AuditableTransitionException $e) {
            $this->assertSame(
                'Incompatibility between [OwenIt\Auditing\Tests\Models\Article:1] and [OwenIt\Auditing\Models\Audit:3]',
                $e->getMessage()
            );

            $this->assertArraySubset([
                'subject',
                'text',
            ], $e->getIncompatibilities(), true);
        }
    }

    /**
     * @group Auditable::transitionTo
     * @test
     *
     * @dataProvider auditableTransitionTestProvider
     *
     * @param bool  $morphMap
     * @param array $oldValues
     * @param array $newValues
     * @param array $oldExpectation
     * @param array $newExpectation
     */
    public function itTransitionsToAnotherModelState(
        bool $morphMap,
        array $oldValues,
        array $newValues,
        array $oldExpectation,
        array $newExpectation
    ) {
        $models = factory(Article::class, 2)->create([
            'title'   => 'Facilis voluptas qui impedit deserunt vitae quidem.',
            'content' => 'Consectetur distinctio nihil eveniet cum. Expedita dolores animi dolorum eos repellat rerum.',
        ]);

        if ($morphMap) {
            Relation::morphMap([
                'articles' => Article::class,
            ]);
        }

        $auditableType = $morphMap ? 'articles' : Article::class;

        $audits = $models->map(function (Article $model) use ($auditableType, $oldValues, $newValues) {
            return factory(Audit::class)->create([
                'auditable_id'   => $model->getKey(),
                'auditable_type' => $auditableType,
                'old_values'     => $oldValues,
                'new_values'     => $newValues,
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
     * @return array
     */
    public function auditableTransitionTestProvider()
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
                    'title'   => 'Nullam egestas interdum eleifend.',
                    'content' => 'Morbi consectetur laoreet sem, eu tempus odio tempor id.',
                ],

                // Expectation when transitioning with old values
                [],

                // Expectation when transitioning with new values
                [
                    'title'   => 'NULLAM EGESTAS INTERDUM ELEIFEND.',
                    'content' => 'Morbi consectetur laoreet sem, eu tempus odio tempor id.',
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
                    'title'   => 'Vivamus a urna et lorem faucibus malesuada nec nec magna.',
                    'content' => 'Mauris ipsum erat, semper non quam vel, sodales tincidunt ligula.',
                ],

                // New values
                [
                    'title'   => 'Nullam egestas interdum eleifend.',
                    'content' => 'Morbi consectetur laoreet sem, eu tempus odio tempor id.',
                ],

                // Expectation when transitioning with old values
                [
                    'title'   => 'VIVAMUS A URNA ET LOREM FAUCIBUS MALESUADA NEC NEC MAGNA.',
                    'content' => 'Mauris ipsum erat, semper non quam vel, sodales tincidunt ligula.',
                ],

                // Expectation when transitioning with new values
                [
                    'title'   => 'NULLAM EGESTAS INTERDUM ELEIFEND.',
                    'content' => 'Morbi consectetur laoreet sem, eu tempus odio tempor id.',
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
                    'title'   => 'Vivamus a urna et lorem faucibus malesuada nec nec magna.',
                    'content' => 'Mauris ipsum erat, semper non quam vel, sodales tincidunt ligula.',
                ],

                // New values
                [],

                // Expectation when transitioning with old values
                [
                    'title'   => 'VIVAMUS A URNA ET LOREM FAUCIBUS MALESUADA NEC NEC MAGNA.',
                    'content' => 'Mauris ipsum erat, semper non quam vel, sodales tincidunt ligula.',
                ],

                // Expectation when transitioning with new values
                [],
            ],
        ];
    }
}
