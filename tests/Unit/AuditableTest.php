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

use Illuminate\Support\Facades\App;
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
    public function it_will_not_audit_models_when_running_from_the_console()
    {
        $this->app['config']->set('audit.console', false);

        $this->assertFalse(Article::isAuditingEnabled());
    }

    /**
     * @group Auditable::isAuditingEnabled
     * @test
     */
    public function it_will_audit_models_when_running_from_the_console()
    {
        $this->app['config']->set('audit.console', true);

        $this->assertTrue(Article::isAuditingEnabled());
    }

    /**
     * @group Auditable::isAuditingEnabled
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
     * @group Auditable::getAuditableEvents
     * @test
     */
    public function it_returns_default_auditable_events()
    {
        $model = new Article();

        $this->assertArraySubset([
            'created',
            'updated',
            'deleted',
            'restored',
        ], $model->getAuditableEvents());

        $this->assertFalse($model->readyForAuditing());
    }

    /**
     * @group Auditable::getAuditableEvents
     * @test
     */
    public function it_returns_custom_auditable_events()
    {
        $model = new Article();

        $model->auditableEvents = [
            'published' => 'publishedHandler',
            'archived',
        ];

        $this->assertArraySubset([
            'published' => 'publishedHandler',
            'archived',
        ], $model->getAuditableEvents());

        $this->assertFalse($model->readyForAuditing());
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::readyForAuditing
     * @test
     */
    public function it_is_not_ready_for_auditing_with_a_custom_event()
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
    public function it_is_ready_for_auditing_with_custom_events()
    {
        $model = new Article();

        $model->auditableEvents = [
            'published' => 'publishedHandler',
            'archived',
        ];

        $model->setAuditEvent('published');
        $this->assertTrue($model->readyForAuditing());

        $model->setAuditEvent('archived');
        $this->assertTrue($model->readyForAuditing());
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::readyForAuditing
     * @test
     */
    public function it_is_ready_for_auditing_with_regular_events()
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
    public function it_fails_when_an_invalid_audit_event_is_set()
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
     */
    public function it_fails_when_a_passed_custom_event_handler_is_missing()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('Unable to handle "published" event, publishedHandler() method missing');

        $model = new Article();

        $model->auditableEvents = [
            'published' => 'publishedHandler',
        ];

        $model->setAuditEvent('published');

        $model->toAudit();
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     */
    public function it_fails_when_a_resolved_custom_event_handler_is_missing()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('Unable to handle "archived" event, auditArchivedAttributes() method missing');

        $model = new Article();

        $model->auditableEvents = [
            'archived',
        ];

        $model->setAuditEvent('archived');

        $model->toAudit();
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     */
    public function it_fails_when_the_user_resolver_implementation_is_invalid()
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
    public function it_returns_audit_data_without_a_resolved_user()
    {
        $this->app['config']->set('audit.user.resolver', User::class);

        $model = factory(Article::class)->make();

        $model->setAuditEvent('created');

        $this->assertCount(10, $auditData = $model->toAudit());

        $this->assertArraySubset([
            'old_values'     => $model->old_values,
            'new_values'     => $model->toArray(),
            'event'          => 'created',
            'auditable_id'   => null,
            'auditable_type' => Article::class,
            'user_id'        => null,
            'url'            => 'console',
            'ip_address'     => '127.0.0.1',
            'user_agent'     => 'Symfony/3.X',
            'tags'           => '',
        ], $auditData);
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     */
    public function it_returns_audit_data_with_a_resolved_user()
    {
        $this->app['config']->set('audit.user.resolver', User::class);

        factory(User::class)->create();

        $model = factory(Article::class)->make();

        $model->setAuditEvent('created');

        $this->assertCount(10, $auditData = $model->toAudit());

        $this->assertArraySubset([
            'old_values'     => $model->old_values,
            'new_values'     => $model->toArray(),
            'event'          => 'created',
            'auditable_id'   => null,
            'auditable_type' => Article::class,
            'user_id'        => 1,
            'url'            => 'console',
            'ip_address'     => '127.0.0.1',
            'user_agent'     => 'Symfony/3.X',
            'tags'           => '',
        ], $auditData);
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::transformAudit
     * @group Auditable::toAudit
     * @test
     */
    public function it_returns_transformed_audit_data()
    {
        $this->markTestIncomplete();
    }

    /**
     * @group Auditable::getAuditInclude
     * @test
     */
    public function it_returns_the_default_attributes_included_in_the_audit()
    {
        $model = new Article();

        $this->assertArraySubset([], $model->getAuditInclude());
    }

    /**
     * @group Auditable::getAuditInclude
     * @test
     */
    public function it_returns_the_custom_attributes_included_in_the_audit()
    {
        $model = new Article();

        $model->auditInclude = [
            'title',
            'content',
        ];

        $this->assertArraySubset([
            'title',
            'content',
        ], $model->getAuditInclude());
    }

    /**
     * @group Auditable::getAuditExclude
     * @test
     */
    public function it_returns_the_default_attributes_excluded_from_the_audit()
    {
        $model = new Article();

        $this->assertArraySubset([], $model->getAuditExclude());
    }

    /**
     * @group Auditable::getAuditExclude
     * @test
     */
    public function it_returns_the_custom_attributes_excluded_from_the_audit()
    {
        $model = new Article();

        $model->auditExclude = [
            'published',
        ];

        $this->assertArraySubset([
            'published',
        ], $model->getAuditExclude());
    }

    /**
     * @group Auditable::getAuditStrict
     * @test
     */
    public function it_returns_the_default_audit_strict_value()
    {
        $model = new Article();

        $this->assertFalse($model->getAuditStrict());
    }

    /**
     * @group Auditable::getAuditStrict
     * @test
     */
    public function it_returns_the_custom_audit_strict_value()
    {
        $model = new Article();

        $model->auditStrict = true;

        $this->assertTrue($model->getAuditStrict());
    }

    /**
     * @group Auditable::getAuditTimestamps
     * @test
     */
    public function it_returns_the_default_audit_timestamps_value()
    {
        $model = new Article();

        $this->assertFalse($model->getAuditTimestamps());
    }

    /**
     * @group Auditable::getAuditTimestamps
     * @test
     */
    public function it_returns_the_custom_audit_timestamps_value()
    {
        $model = new Article();

        $model->auditTimestamps = true;

        $this->assertTrue($model->getAuditTimestamps());
    }

    /**
     * @group Auditable::getAuditDriver
     * @test
     */
    public function it_returns_the_default_audit_driver_value()
    {
        $model = new Article();

        $this->assertNull($model->getAuditDriver());
    }

    /**
     * @group Auditable::getAuditDriver
     * @test
     */
    public function it_returns_the_custom_audit_driver_value()
    {
        $model = new Article();

        $model->auditDriver = 'RedisDriver';

        $this->assertEquals('RedisDriver', $model->getAuditDriver());
    }

    /**
     * @group Auditable::getAuditThreshold
     * @test
     */
    public function it_returns_the_default_audit_threshold_value()
    {
        $model = new Article();

        $this->assertEquals(0, $model->getAuditThreshold());
    }

    /**
     * @group Auditable::getAuditThreshold
     * @test
     */
    public function it_returns_the_custom_audit_threshold_value()
    {
        $model = new Article();

        $model->auditThreshold = 10;

        $this->assertEquals(10, $model->getAuditThreshold());
    }

    /**
     * @group Auditable::generateTags
     * @test
     */
    public function it_returns_the_default_audit_tags()
    {
        $model = new Article();

        $this->assertArraySubset([], $model->generateTags());
    }

    /**
     * @group Auditable::generateTags
     * @test
     */
    public function it_returns_the_custom_audit_tags()
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
        ], $model->generateTags());
    }

    /**
     * @group Auditable::transitionTo
     * @test
     */
    public function it_fails_to_transition_when_the_auditable_type_of_the_audit_does_not_match_the_model()
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
    public function it_fails_to_transition_when_the_auditable_id_of_the_audit_does_not_match_the_model()
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
    public function it_fails_to_transition_when_the_auditable_attribute_compatibility_is_not_met()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('Incompatibility between OwenIt\Auditing\Tests\Models\Article [id:1] and OwenIt\Auditing\Models\Audit [id:3]. Missing attributes: [subject, text]');

        $model = factory(Article::class)->create();
        $incompatibleAudit = factory(Audit::class)->create([
            'event'          => 'updated',
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
     */
    public function it_transitions_to_a_new_model_state()
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
                'title'   => 'Facilis voluptas qui impedit deserunt vitae quidem.',
                'content' => 'Consectetur distinctio nihil eveniet cum. Expedita dolores animi dolorum eos repellat rerum.',
            ],
            'new_values'     => [
                'title'   => 'Culpa qui rerum excepturi quisquam quia officiis.',
                'content' => 'Magnam enim suscipit officiis tempore ut quis harum.',
            ],
        ]);

        $this->assertTrue($model->transitionTo($audit));
    }
}
