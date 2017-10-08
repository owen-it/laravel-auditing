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

use Illuminate\Support\Facades\Config;
use Mockery;
use Orchestra\Testbench\TestCase;
use OwenIt\Auditing\Models\Audit;
use OwenIt\Auditing\Tests\Stubs\AuditableStub;
use OwenIt\Auditing\Tests\Stubs\UserStub;

class AuditModelTest extends TestCase
{
    /**
     * Set test attributes to an Audit instance.
     *
     * @param Audit $audit
     * @param bool  $withUser
     *
     * @return void
     */
    private function setAuditTestAttributes(Audit $audit, $withUser = true)
    {
        $audit->id = 1;
        $audit->event = 'created';
        $audit->url = 'http://example.com/create';
        $audit->ip_address = '127.0.0.1';
        $audit->user_agent = 'Mozilla/5.0 (X11; Linux x86_64; rv:53.0) Gecko/20100101 Firefox/53.0';
        $audit->created_at = '2012-06-14 15:03:00';
        $audit->updated_at = '2012-06-14 15:03:00';
        $audit->relation_id = null;
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

        $audit->setRelation('user', $withUser ? new UserStub() : null);
    }

    /**
     * Test Audit resolveData method to PASS (with User).
     *
     * @return void
     */
    public function testResolveDataPassWithUser()
    {
        $audit = new Audit();
        $this->setAuditTestAttributes($audit);

        $data = $audit->resolveData();

        $this->assertCount(16, $data);

        $this->assertArraySubset([
            'audit_id'         => 1,
            'audit_event'      => 'created',
            'audit_url'        => 'http://example.com/create',
            'audit_ip_address' => '127.0.0.1',
            'audit_user_agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:53.0) Gecko/20100101 Firefox/53.0',
            'audit_created_at' => '2012-06-14 15:03:00',
            'audit_updated_at' => '2012-06-14 15:03:00',
            'user_id'          => 123,
            'user_email'       => 'bob@example.com',
            'user_name'        => 'Bob',
            'new_title'        => 'How To Audit Eloquent Models',
            'new_content'      => 'First step: install the laravel-auditing package.',
            'new_published'    => 1,
            'old_title'        => 'How to audit models',
            'old_content'      => 'This is a draft.',
            'old_published'    => 0,
            'relation_id'      => 0,
        ], $data);
    }

    /**
     * Test Audit resolveData method to PASS (without User).
     *
     * @return void
     */
    public function testResolveDataPassWithoutUser()
    {
        $audit = new Audit();
        $this->setAuditTestAttributes($audit, false);

        $data = $audit->resolveData();

        $this->assertCount(14, $data);

        $this->assertArraySubset([
            'audit_id'         => 1,
            'audit_event'      => 'created',
            'audit_url'        => 'http://example.com/create',
            'audit_ip_address' => '127.0.0.1',
            'audit_user_agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:53.0) Gecko/20100101 Firefox/53.0',
            'audit_created_at' => '2012-06-14 15:03:00',
            'audit_updated_at' => '2012-06-14 15:03:00',
            'user_id'          => null,
            'new_title'        => 'How To Audit Eloquent Models',
            'new_content'      => 'First step: install the laravel-auditing package.',
            'new_published'    => 1,
            'old_title'        => 'How to audit models',
            'old_content'      => 'This is a draft.',
            'old_published'    => 0,
            'relation_id'      => 0,
        ], $data);
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

        $auditable = Mockery::mock(AuditableStub::class)
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
     * Test Audit getMetadata method to PASS (default, with User).
     *
     * @return void
     */
    public function testGetMetadataPassDefaultWithUser()
    {
        $audit = Mockery::mock(Audit::class)
            ->makePartial();

        $this->setAuditTestAttributes($audit);

        $metadata = $audit->getMetadata();

        $this->assertCount(10, $metadata);

        $this->assertArraySubset([
            'audit_id'         => 1,
            'audit_event'      => 'created',
            'audit_url'        => 'http://example.com/create',
            'audit_ip_address' => '127.0.0.1',
            'audit_user_agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:53.0) Gecko/20100101 Firefox/53.0',
            'audit_created_at' => '2012-06-14 15:03:00',
            'audit_updated_at' => '2012-06-14 15:03:00',
            'user_id'          => 123,
            'user_email'       => 'bob@example.com',
            'user_name'        => 'Bob',
            'relation_id'      => 0,
        ], $metadata);
    }

    /**
     * Test Audit getMetadata method to PASS (default, without User).
     *
     * @return void
     */
    public function testGetMetadataPassDefaultWithoutUser()
    {
        $audit = Mockery::mock(Audit::class)
            ->makePartial();

        $this->setAuditTestAttributes($audit, false);

        $metadata = $audit->getMetadata();

        $this->assertCount(10, $metadata);

        $this->assertArraySubset([
            'audit_id'         => 1,
            'audit_event'      => 'created',
            'audit_url'        => 'http://example.com/create',
            'audit_ip_address' => '127.0.0.1',
            'audit_user_agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:53.0) Gecko/20100101 Firefox/53.0',
            'audit_created_at' => '2012-06-14 15:03:00',
            'audit_updated_at' => '2012-06-14 15:03:00',
            'user_id'          => null,
            'relation_id'      => 0,
        ], $metadata);
    }

    /**
     * Test Audit getMetadata method to PASS (JSON, with User).
     *
     * @return void
     */
    public function testGetMetadataPassJsonWithUser()
    {
        $audit = Mockery::mock(Audit::class)
            ->makePartial();

        $this->setAuditTestAttributes($audit);

        $metadata = $audit->getMetadata(true, JSON_PRETTY_PRINT);

        $expected = <<< EOF
{
    "audit_id": 1,
    "audit_event": "created",
    "audit_url": "http:\/\/example.com\/create",
    "audit_ip_address": "127.0.0.1",
    "audit_user_agent": "Mozilla\/5.0 (X11; Linux x86_64; rv:53.0) Gecko\/20100101 Firefox\/53.0",
    "audit_created_at": "2012-06-14 15:03:00",
    "audit_updated_at": "2012-06-14 15:03:00",
    "user_id": 123,
    "user_email": "bob@example.com",
    "user_name": "Bob"
    "relation_id": null
}
EOF;

        $this->assertEquals($expected, $metadata);
    }

    /**
     * Test Audit getMetadata method to PASS (JSON, without User).
     *
     * @return void
     */
    public function testGetMetadataPassJsonWithoutUser()
    {
        $audit = Mockery::mock(Audit::class)
            ->makePartial();

        $this->setAuditTestAttributes($audit, false);

        $metadata = $audit->getMetadata(true, JSON_PRETTY_PRINT);

        $expected = <<< EOF
{
    "audit_id": 1,
    "audit_event": "created",
    "audit_url": "http:\/\/example.com\/create",
    "audit_ip_address": "127.0.0.1",
    "audit_user_agent": "Mozilla\/5.0 (X11; Linux x86_64; rv:53.0) Gecko\/20100101 Firefox\/53.0",
    "audit_created_at": "2012-06-14 15:03:00",
    "audit_updated_at": "2012-06-14 15:03:00",
    "user_id": null,
    "relation_id": null
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

        $auditable = Mockery::mock(AuditableStub::class)
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

        $auditable = Mockery::mock(AuditableStub::class)
            ->makePartial();

        $audit->auditable = $auditable;

        $modified = $audit->getModified(true, JSON_PRETTY_PRINT);

        $expected = <<< 'EOF'
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

    /**
     * Test Audit user() relation method to PASS (custom keys).
     *
     * @return void
     */
    public function testUserPassCustomKeys()
    {
        $audit = Mockery::mock(Audit::class)
            ->makePartial();

        Config::set('audit.user.model', UserStub::class);
        Config::set('audit.user.primary_key', 'pk_id');
        Config::set('audit.user.foreign_key', 'fk_id');

        $this->assertInstanceOf(UserStub::class, $audit->user()->getRelated());

        // Up to Laravel 5.3, the ownerKey attribute was called otherKey
        if (method_exists($audit->user(), 'getOtherKey')) {
            $this->assertEquals('pk_id', $audit->user()->getOtherKey());
        }

        // From Laravel 5.4 onward, the otherKey attribute was renamed to ownerKey
        if (method_exists($audit->user(), 'getOwnerKey')) {
            $this->assertEquals('pk_id', $audit->user()->getOwnerKey());
        }

        $this->assertEquals('fk_id', $audit->user()->getForeignKey());
    }
}
