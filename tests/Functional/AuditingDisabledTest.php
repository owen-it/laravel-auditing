<?php

namespace OwenIt\Auditing\Tests\Functional;

use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use OwenIt\Auditing\Events\AuditCustom;
use OwenIt\Auditing\Events\DispatchAudit;
use OwenIt\Auditing\Models\Audit;
use OwenIt\Auditing\Tests\AuditingTestCase;
use OwenIt\Auditing\Tests\Models\Article;

class AuditingDisabledTest extends AuditingTestCase
{
    protected bool $auditingEnabled = false;

    public function test_it_will_not_audit_the_events_when_auditing_is_disabled(): void
    {
        $article = Article::factory()->create([
            'title' => 'How To Audit Eloquent Models',
            'content' => 'N/A',
            'published_at' => null,
            'reviewed' => 0,
        ]);
        $article->update([
            'content' => 'First step: install the laravel-auditing package.',
            'published_at' => Carbon::now(),
            'reviewed' => 1,
        ]);
        $article->delete();
        $article->restore();
        
        $audit = Audit::first();

        $this->assertNull($audit);
    }

    public function test_event_does_not_audit_when_auditing_is_disabled(): void
    {
        $article = Article::factory()->create();
        $article->auditEvent = 'whateverYouWant';
        $article->isCustomEvent = true;
        $article->auditCustomOld = ['customExample' => 'Anakin Skywalker'];
        $article->auditCustomNew = ['customExample' => 'Darth Vader'];

        $auditCountBefore = Audit::where('auditable_type', Article::class)->count();

        Event::dispatch(new DispatchAudit($article));

        $this->assertSame($auditCountBefore, Audit::where('auditable_type', Article::class)->count());
    }

    public function test_event_does_not_audit_when_running_in_console_without_console_flag(): void
    {
        App::shouldReceive('runningInConsole')->andReturn(true);
        config(['audit.console' => false]);

        $article = Article::factory()->create();
        $article->auditEvent = 'whateverYouWant';
        $article->isCustomEvent = true;
        $article->auditCustomOld = ['customExample' => 'Anakin Skywalker'];
        $article->auditCustomNew = ['customExample' => 'Darth Vader'];

        $auditCountBefore = Audit::where('auditable_type', Article::class)->count();

        Event::dispatch(new DispatchAudit($article));
        $this->assertSame($auditCountBefore, Audit::where('auditable_type', Article::class)->count());
    }

    public function test_custom_event_does_not_audit_when_auditing_is_disabled(): void
    {
        $article = Article::factory()->create();
        $article->auditEvent = 'whateverYouWant';
        $article->isCustomEvent = true;
        $article->auditCustomOld = ['customExample' => 'Anakin Skywalker'];
        $article->auditCustomNew = ['customExample' => 'Darth Vader'];

        $auditCountBefore = Audit::where('auditable_type', Article::class)->count();

        Event::dispatch(new AuditCustom($article));

        $this->assertSame($auditCountBefore, Audit::where('auditable_type', Article::class)->count());
    }

    public function test_custom_event_does_not_audit_when_running_in_console_without_console_flag(): void
    {
        App::shouldReceive('runningInConsole')->andReturn(true);
        config(['audit.console' => false]);

        $article = Article::factory()->create();
        $article->auditEvent = 'whateverYouWant';
        $article->isCustomEvent = true;
        $article->auditCustomOld = ['customExample' => 'Anakin Skywalker'];
        $article->auditCustomNew = ['customExample' => 'Darth Vader'];

        $auditCountBefore = Audit::where('auditable_type', Article::class)->count();

        Event::dispatch(new AuditCustom($article));
        $this->assertSame($auditCountBefore, Audit::where('auditable_type', Article::class)->count());
    }
}
