<?php

namespace OwenIt\Auditing\Tests\Unit;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use OwenIt\Auditing\Events\DispatchAudit;
use OwenIt\Auditing\Tests\Models\Article;
use Illuminate\Events\CallQueuedListener;
use OwenIt\Auditing\Tests\AuditingTestCase;
use OwenIt\Auditing\Listeners\ProcessDispatchAudit;

class ProcessDispatchAuditTest extends AuditingTestCase
{
    /**
     * @test
     */
    public function itIsListeningToTheCorrectEvent()
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

    /**
     * @test
     */
    public function itGetsProperlyQueued()
    {
        Queue::fake();

        $model = factory(Article::class)->create();

        app()->make('events')->dispatch(new DispatchAudit($model));

        Queue::assertPushed(CallQueuedListener::class, function ($job) use ($model) {
            return $job->class == ProcessDispatchAudit::class
                && $job->data[0] instanceof DispatchAudit
                && $job->data[0]->model->is($model);
        });
    }

    /**
     * @test
     */
    public function itCanHaveConnectionAndQueueSet()
    {
        $this->app['config']->set('audit.queue.connection', 'redis');
        $this->app['config']->set('audit.queue.queue', 'audits');
        $this->app['config']->set('audit.queue.delay', 60);

        Queue::fake();

        $model = factory(Article::class)->create();

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