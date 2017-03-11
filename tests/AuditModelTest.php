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
use Mockery;
use Orchestra\Testbench\TestCase;
use OwenIt\Auditing\Models\Audit;
use OwenIt\Auditing\Tests\Stubs\AuditableModelStub;

class AuditModelTest extends TestCase
{
    /**
     * Set test attributes to an Audit instance.
     *
     * @param Audit $audit
     *
     * @return void
     */
    private function setAuditTestAttributes(Audit $audit)
    {
        $audit->id = 1;
        $audit->event = 'created';
        $audit->url = 'http://example.com/create';
        $audit->ip_address = '127.0.0.1';
        $audit->created_at = Carbon::now();
        $audit->user_id = 1;
        $audit->new_values = [
            'title'     => 'How To Audit Eloquent Models',
            'content'   => 'First step: install the laravel-auditing package.',
            'published' => 1,
        ];
        $audit->old_values = [
            'title'     => 'How to audit models',
            'content'   => 'This is a draft.',
            'published' => 0,
        ];
    }

    /**
     * Test Audit resolveData method to PASS.
     *
     * @return void
     */
    public function testResolveDataPass()
    {
        $audit = new Audit();
        $this->setAuditTestAttributes($audit);

        $data = $audit->resolveData();

        $this->assertCount(12, $data);

        $this->assertArrayHasKey('audit_id', $data);
        $this->assertArrayHasKey('audit_event', $data);
        $this->assertArrayHasKey('audit_url', $data);
        $this->assertArrayHasKey('audit_ip_address', $data);
        $this->assertArrayHasKey('audit_created_at', $data);
        $this->assertArrayHasKey('user_id', $data);
        $this->assertArrayHasKey('new_title', $data);
        $this->assertArrayHasKey('new_content', $data);
        $this->assertArrayHasKey('new_published', $data);
        $this->assertArrayHasKey('old_title', $data);
        $this->assertArrayHasKey('old_content', $data);
        $this->assertArrayHasKey('old_published', $data);
    }

    /**
     * Test Audit getDataValue method to PASS.
     *
     * @return void
     */
    public function testGetDataValuePass()
    {
        $audit = Mockery::mock(Audit::class)
            ->makePartial();

        $this->setAuditTestAttributes($audit);

        $auditable = Mockery::mock(AuditableModelStub::class)
            ->makePartial();

        $audit->auditable = $auditable;

        // Resolve data, making it available to the getDataValue() method
        $audit->resolveData();

        // Mutate value
        $this->assertEquals('HOW TO AUDIT ELOQUENT MODELS', $audit->getDataValue('new_title'));

        // Cast value
        $this->assertTrue($audit->getDataValue('new_published'));

        // Original value
        $this->assertEquals('First step: install the laravel-auditing package.', $audit->getDataValue('new_content'));

        // Invalid value
        $this->assertNull($audit->getDataValue('invalid_key'));
    }

    /**
     * Test Audit getMetadata method to PASS (default).
     *
     * @return void
     */
    public function testGetMetadataPassDefault()
    {
        $audit = Mockery::mock(Audit::class)
            ->makePartial();

        $this->setAuditTestAttributes($audit);

        $metadata = $audit->getMetadata();

        $this->assertCount(6, $metadata);

        $this->assertArrayHasKey('audit_id', $metadata);
        $this->assertArrayHasKey('audit_event', $metadata);
        $this->assertArrayHasKey('audit_url', $metadata);
        $this->assertArrayHasKey('audit_ip_address', $metadata);
        $this->assertArrayHasKey('audit_created_at', $metadata);
        $this->assertArrayHasKey('user_id', $metadata);
    }

    /**
     * Test Audit getMetadata method to PASS (JSON).
     *
     * @return void
     */
    public function testGetMetadataPassJson()
    {
        $audit = Mockery::mock(Audit::class)
            ->makePartial();

        $this->setAuditTestAttributes($audit);

        $metadata = $audit->getMetadata(true, JSON_PRETTY_PRINT);

        $now = Carbon::now()->toDateTimeString();

        $expected = <<< EOF
{
    "audit_id": 1,
    "audit_event": "created",
    "audit_url": "http:\/\/example.com\/create",
    "audit_ip_address": "127.0.0.1",
    "audit_created_at": "$now",
    "user_id": 1
}
EOF;

        $this->assertEquals($expected, $metadata);
    }

    /**
     * Test Audit getModified method to PASS (default).
     *
     * @return void
     */
    public function testGetModifiedPassDefault()
    {
        $audit = Mockery::mock(Audit::class)
            ->makePartial();

        $this->setAuditTestAttributes($audit);

        $auditable = Mockery::mock(AuditableModelStub::class)
            ->makePartial();

        $audit->auditable = $auditable;

        $modified = $audit->getModified();

        $this->assertCount(3, $modified);

        $this->assertArrayHasKey('title', $modified);
        $this->assertArrayHasKey('content', $modified);
        $this->assertArrayHasKey('published', $modified);
    }

    /**
     * Test Audit getModified method to PASS (JSON).
     *
     * @return void
     */
    public function testGetModifiedPassJson()
    {
        $audit = Mockery::mock(Audit::class)
            ->makePartial();

        $this->setAuditTestAttributes($audit);

        $auditable = Mockery::mock(AuditableModelStub::class)
            ->makePartial();

        $audit->auditable = $auditable;

        $modified = $audit->getModified(true, JSON_PRETTY_PRINT);

        $expected = <<< EOF
{
    "title": {
        "new": "HOW TO AUDIT ELOQUENT MODELS",
        "old": "HOW TO AUDIT MODELS"
    },
    "content": {
        "new": "First step: install the laravel-auditing package.",
        "old": "This is a draft."
    },
    "published": {
        "new": true,
        "old": false
    }
}
EOF;

        $this->assertEquals($expected, $modified);
    }
}
