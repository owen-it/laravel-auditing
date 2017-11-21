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

use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Exceptions\AuditingException;
use OwenIt\Auditing\Models\Audit;
use OwenIt\Auditing\Tests\Models\Article;
use OwenIt\Auditing\Tests\Models\User;

class AuditableTest extends AuditingTestCase
{
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
    public function itFailsWhenTheUserResolverImplementationIsInvalid()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('Invalid UserResolver implementation');

        $this->app['config']->set('audit.user.resolver', null);

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
        $this->app['config']->set('audit.user.resolver', User::class);

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
                'published_at' => $now->format('Y-m-d H:i:s')

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
        $this->app['config']->set('audit.user.resolver', User::class);

        factory(User::class)->create();

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
                'published_at' => $now->format('Y-m-d H:i:s')
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
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('Expected Audit for OwenIt\Auditing\Tests\Models\Article, got Audit for OwenIt\Auditing\Tests\Models\User instead');

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
    public function itFailsToTransitionWhenTheAuditAuditableIdDoesNotMatchTheModelId()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('Expected Auditable id 2, got 1 instead');

        $firstAudit = factory(Article::class)->create()->audits()->first();
        $secondModel = factory(Article::class)->create();

        $secondModel->transitionTo($firstAudit);
    }

    /**
     * @group Auditable::transitionTo
     * @test
     */
    public function itFailsToTransitionWhenTheAuditableAttributeCompatibilityIsNotMet()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('Incompatibility between OwenIt\Auditing\Tests\Models\Article [id:1] and OwenIt\Auditing\Models\Audit [id:3]. Missing attributes: [subject, text]');

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

        $model->transitionTo($incompatibleAudit);
    }

    /**
     * @group Auditable::transitionTo
     * @test
     *
     * @dataProvider auditableTransitionTestProvider
     *
     * @param bool  $useOldValues
     * @param array $expectations
     */
    public function itTransitionsToAnotherModelState(bool $useOldValues, array $expectations)
    {
        $model = factory(Article::class)->create([
            'title'   => 'Facilis voluptas qui impedit deserunt vitae quidem.',
            'content' => 'Consectetur distinctio nihil eveniet cum. Expedita dolores animi dolorum eos repellat rerum.',
        ]);

        $audit = factory(Audit::class)->create([
            'event'          => 'updated',
            'auditable_id'   => $model->getKey(),
            'auditable_type' => Article::class,
            'old_values'     => [
                'title'   => 'Vivamus a urna et lorem faucibus malesuada nec nec magna.',
                'content' => 'Mauris ipsum erat, semper non quam vel, sodales tincidunt ligula.',
            ],
            'new_values' => [
                'title'   => 'Nullam egestas interdum eleifend.',
                'content' => 'Morbi consectetur laoreet sem, eu tempus odio tempor id.',
            ],
        ]);

        $this->assertInstanceOf(Auditable::class, $model->transitionTo($audit, $useOldValues));

        $this->assertSame($expectations, $model->getDirty());
    }

    /**
     * @return array
     */
    public function auditableTransitionTestProvider()
    {
        return [
            [
                true,
                [
                    'title'   => 'VIVAMUS A URNA ET LOREM FAUCIBUS MALESUADA NEC NEC MAGNA.',
                    'content' => 'Mauris ipsum erat, semper non quam vel, sodales tincidunt ligula.',
                ],
            ],
            [
                false,
                [
                    'title'   => 'NULLAM EGESTAS INTERDUM ELEIFEND.',
                    'content' => 'Morbi consectetur laoreet sem, eu tempus odio tempor id.',
                ],
            ],
        ];
    }
}
