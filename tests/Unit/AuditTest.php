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

use Mockery;
use OwenIt\Auditing\Models\Audit;
use OwenIt\Auditing\Tests\Models\Article;
use OwenIt\Auditing\Tests\Models\User;

class AuditTest extends AuditingTestCase
{
    /**
     * @group Audit::user
     * @test
     */
    public function itRelatesToUserWithCustomKeys()
    {
        $audit = Mockery::mock(Audit::class)
            ->makePartial();

        $this->app['config']->set('audit.user.model', User::class);
        $this->app['config']->set('audit.user.primary_key', 'pk_id');
        $this->app['config']->set('audit.user.foreign_key', 'fk_id');

        $this->assertInstanceOf(User::class, $audit->user()->getRelated());

        // Up to Laravel 5.3, the ownerKey attribute was called otherKey
        if (method_exists($audit->user(), 'getOtherKey')) {
            $this->assertSame('pk_id', $audit->user()->getOtherKey());
        }

        // From Laravel 5.4 onward, the otherKey attribute was renamed to ownerKey
        if (method_exists($audit->user(), 'getOwnerKey')) {
            $this->assertSame('pk_id', $audit->user()->getOwnerKey());
        }

        $this->assertSame('fk_id', $audit->user()->getForeignKey());
    }

    /**
     * @group Audit::resolveData
     * @test
     */
    public function itResolvesAuditData()
    {
        $audit = factory(Article::class)->create()->audits()->first();

        $this->assertCount(13, $resolvedData = $audit->resolveData());

        $this->assertArraySubset([
            'audit_id',
            'audit_event',
            'audit_url',
            'audit_ip_address',
            'audit_user_agent',
            'audit_tags',
            'audit_created_at',
            'audit_updated_at',
            'user_id',
            'new_title',
            'new_content',
            'new_published',
            'new_id',
        ], array_keys($resolvedData));
    }

    /**
     * @group Audit::resolveData
     * @test
     */
    public function itResolvesAuditDataIncludingUserAttributes()
    {
        factory(User::class)->create();
        $audit = factory(Article::class)->create()->audits()->first();

        $this->assertCount(18, $resolvedData = $audit->resolveData());

        $this->assertArraySubset([
            'audit_id',
            'audit_event',
            'audit_url',
            'audit_ip_address',
            'audit_user_agent',
            'audit_tags',
            'audit_created_at',
            'audit_updated_at',
            'user_id',
            'user_first_name',
            'user_last_name',
            'user_email',
            'user_created_at',
            'user_updated_at',
            'new_title',
            'new_content',
            'new_published',
            'new_id',
        ], array_keys($resolvedData));
    }

    /**
     * @group Audit::resolveData
     * @group Audit::getDataValue
     * @test
     */
    public function itReturnsTheAppropriateAuditableDataValues()
    {
        $audit = factory(Article::class)->create([
            'title'     => 'How To Audit Eloquent Models',
            'content'   => 'First step: install the laravel-auditing package.',
            'published' => 1,
        ])->audits()->first();

        // Resolve data, making it available to the getDataValue() method
        $this->assertCount(13, $audit->resolveData());

        // Mutate value
        $this->assertSame('HOW TO AUDIT ELOQUENT MODELS', $audit->getDataValue('new_title'));

        // Cast value
        $this->assertTrue($audit->getDataValue('new_published'));

        // Original value
        $this->assertSame('First step: install the laravel-auditing package.', $audit->getDataValue('new_content'));

        // Invalid value
        $this->assertNull($audit->getDataValue('invalid_key'));
    }

    /**
     * @group Audit::getMetadata
     * @test
     */
    public function itReturnsAuditMetadataAsArray()
    {
        $audit = factory(Article::class)->create()->audits()->first();

        $this->assertCount(9, $metadata = $audit->getMetadata());

        $this->assertArraySubset([
            'audit_id'         => 1,
            'audit_event'      => 'created',
            'audit_url'        => 'console',
            'audit_ip_address' => '127.0.0.1',
            'audit_user_agent' => 'Symfony/3.X',
            'audit_tags'       => [],
            'audit_created_at' => $audit->created_at,
            'audit_updated_at' => $audit->updated_at,
            'user_id'          => null,
        ], $metadata);
    }

    /**
     * @group Audit::getMetadata
     * @test
     */
    public function itReturnsAuditMetadataIncludingUserAttributesAsArray()
    {
        $user = factory(User::class)->create([
            'first_name' => 'Rick',
            'last_name'  => 'Sanchez',
            'email'      => 'rick@wubba-lubba-dub.dub',
        ]);
        $audit = factory(Article::class)->create()->audits()->first();

        $this->assertCount(14, $metadata = $audit->getMetadata());

        $this->assertArraySubset([
            'audit_id'         => 2,
            'audit_event'      => 'created',
            'audit_url'        => 'console',
            'audit_ip_address' => '127.0.0.1',
            'audit_user_agent' => 'Symfony/3.X',
            'audit_tags'       => [],
            'audit_created_at' => $audit->created_at,
            'audit_updated_at' => $audit->updated_at,
            'user_id'          => 1,
            'user_first_name'  => 'Rick',
            'user_last_name'   => 'Sanchez',
            'user_email'       => 'rick@wubba-lubba-dub.dub',
            'user_created_at'  => $user->created_at,
            'user_updated_at'  => $user->updated_at,
        ], $metadata);
    }

    /**
     * @group Audit::getMetadata
     * @test
     */
    public function itReturnsAuditMetadataAsJsonString()
    {
        $audit = factory(Article::class)->create()->audits()->first();

        $metadata = $audit->getMetadata(true, JSON_PRETTY_PRINT);

        $expected = <<< EOF
{
    "audit_id": 1,
    "audit_event": "created",
    "audit_url": "console",
    "audit_ip_address": "127.0.0.1",
    "audit_user_agent": "Symfony\/3.X",
    "audit_tags": [],
    "audit_created_at": "$audit->created_at",
    "audit_updated_at": "$audit->updated_at",
    "user_id": null
}
EOF;

        $this->assertSame($expected, $metadata);
    }

    /**
     * @group Audit::getMetadata
     * @test
     */
    public function itReturnsAuditMetadataIncludingUserAttributesAsJsonString()
    {
        $user = factory(User::class)->create([
            'first_name' => 'Rick',
            'last_name'  => 'Sanchez',
            'email'      => 'rick@wubba-lubba-dub.dub',
        ]);
        $audit = factory(Article::class)->create()->audits()->first();

        $metadata = $audit->getMetadata(true, JSON_PRETTY_PRINT);

        $expected = <<< EOF
{
    "audit_id": 2,
    "audit_event": "created",
    "audit_url": "console",
    "audit_ip_address": "127.0.0.1",
    "audit_user_agent": "Symfony\/3.X",
    "audit_tags": [],
    "audit_created_at": "$audit->created_at",
    "audit_updated_at": "$audit->updated_at",
    "user_id": 1,
    "user_first_name": "Rick",
    "user_last_name": "Sanchez",
    "user_email": "rick@wubba-lubba-dub.dub",
    "user_created_at": "$user->created_at",
    "user_updated_at": "$user->updated_at"
}
EOF;

        $this->assertSame($expected, $metadata);
    }

    /**
     * @group Audit::getModified
     * @test
     */
    public function itReturnsAuditableModifiedAttributesAsArray()
    {
        $audit = factory(Article::class)->create([
            'title'     => 'How To Audit Eloquent Models',
            'content'   => 'First step: install the laravel-auditing package.',
            'published' => 1,
        ])->audits()->first();

        $this->assertCount(4, $modified = $audit->getModified());

        $this->assertArraySubset([
            'title' => [
                'new' => 'HOW TO AUDIT ELOQUENT MODELS',
            ],
            'content' => [
                'new' => 'First step: install the laravel-auditing package.',
            ],
            'published' => [
                'new' => true,
            ],
            'id' => [
                'new' => 1,
            ],
        ], $modified);
    }

    /**
     * @group Audit::getModified
     * @test
     */
    public function itReturnsAuditableModifiedAttributesAsJsonString()
    {
        $audit = factory(Article::class)->create([
            'title'     => 'How To Audit Eloquent Models',
            'content'   => 'First step: install the laravel-auditing package.',
            'published' => 1,
        ])->audits()->first();

        $modified = $audit->getModified(true, JSON_PRETTY_PRINT);

        $expected = <<< 'EOF'
{
    "title": {
        "new": "HOW TO AUDIT ELOQUENT MODELS"
    },
    "content": {
        "new": "First step: install the laravel-auditing package."
    },
    "published": {
        "new": true
    },
    "id": {
        "new": 1
    }
}
EOF;

        $this->assertSame($expected, $modified);
    }
}
