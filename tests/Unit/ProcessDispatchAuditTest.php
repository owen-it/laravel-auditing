<?php

namespace OwenIt\Auditing\Tests\Unit;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use OwenIt\Auditing\Events\DispatchAudit;
use OwenIt\Auditing\Listeners\ProcessDispatchAudit;
use OwenIt\Auditing\Tests\AuditingTestCase;
use OwenIt\Auditing\Tests\Models\Article;

class ProcessDispatchAuditTest extends AuditingTestCase
{
    public function test_it_is_listening_to_the_correct_event(): void
    {
        if (version_compare($this->app->version(), '8.0.0', '<')) {
            $this->markTestSkipped('This test is only for Laravel 8.0.0+');
        }

        Event::fake();

        Event::assertListening(
            DispatchAudit::class,
            ProcessDispatchAudit::class
        );
    }

    public function test_it_gets_properly_queued(): void
    {
        Queue::fake();

        $model = Article::factory()->create();

        app()->make('events')->dispatch(new DispatchAudit($model));

        Queue::assertPushed(CallQueuedListener::class, function ($job) use ($model) {
            return $job->class == ProcessDispatchAudit::class
                && $job->data[0] instanceof DispatchAudit
                && $job->data[0]->model->is($model);
        });
    }

    public function test_it_can_have_connection_and_queue_set(): void
    {
        $this->app['config']->set('audit.queue.connection', 'redis');
        $this->app['config']->set('audit.queue.queue', 'audits');
        $this->app['config']->set('audit.queue.delay', 60);

        Queue::fake();

        $model = Article::factory()->create();

        app()->make('events')->dispatch(new DispatchAudit($model));

        Queue::assertPushedOn('audits', CallQueuedListener::class, function ($job) use ($model) {
            $instantiatedJob = new $job->class;

            return $job->class == ProcessDispatchAudit::class
                && $job->data[0] instanceof DispatchAudit
                && $job->data[0]->model->is($model)
                && $instantiatedJob->viaConnection() == 'redis'
                && $instantiatedJob->withDelay(new DispatchAudit($model)) == 60;
        });
    }
}
