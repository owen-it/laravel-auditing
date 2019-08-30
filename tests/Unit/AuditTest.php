<?php

namespace OwenIt\Auditing\Tests;

use Carbon\Carbon;
use DateTimeInterface;
use OwenIt\Auditing\Encoders\Base64Encoder;
use OwenIt\Auditing\Models\Audit;
use OwenIt\Auditing\Redactors\LeftRedactor;
use OwenIt\Auditing\Tests\Models\Article;
use OwenIt\Auditing\Tests\Models\User;

class AuditTest extends AuditingTestCase
{
    /**
     * @group Audit::resolveData
     * @test
     */
    public function itResolvesAuditData()
    {
        $now = Carbon::now();

        $article = factory(Article::class)->create([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'First step: install the laravel-auditing package.',
            'reviewed'     => 1,
            'published_at' => $now,
        ]);

        $audit = $article->audits()->first();

        $this->assertCount(15, $resolvedData = $audit->resolveData());

        $this->assertArraySubset([
            'audit_id'         => 1,
            'audit_event'      => 'created',
            'audit_url'        => 'console',
            'audit_ip_address' => '127.0.0.1',
            'audit_user_agent' => 'Symfony',
            'audit_tags'       => null,
            'audit_created_at' => $audit->created_at->toDateTimeString(),
            'audit_updated_at' => $audit->updated_at->toDateTimeString(),
            'user_id'          => null,
            'user_type'        => null,
            'new_title'        => 'How To Audit Eloquent Models',
            'new_content'      => 'First step: install the laravel-auditing package.',
            'new_published_at' => $now->toDateTimeString(),
            'new_reviewed'     => 1,
            'new_id'           => 1,
        ], $resolvedData, true);
    }

    /**
     * @group Audit::resolveData
     * @test
     */
    public function itResolvesAuditDataIncludingUserAttributes()
    {
        $now = Carbon::now();

        $user = factory(User::class)->create([
            'is_admin'   => 1,
            'first_name' => 'rick',
            'last_name'  => 'Sanchez',
            'email'      => 'rick@wubba-lubba-dub.dub',
        ]);

        $this->actingAs($user);

        $article = factory(Article::class)->create([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'First step: install the laravel-auditing package.',
            'reviewed'     => 1,
            'published_at' => $now,
        ]);

        $audit = $article->audits()->first();

        $this->assertCount(21, $resolvedData = $audit->resolveData());

        $this->assertArraySubset([
            'audit_id'         => 2,
            'audit_event'      => 'created',
            'audit_url'        => 'console',
            'audit_ip_address' => '127.0.0.1',
            'audit_user_agent' => 'Symfony',
            'audit_tags'       => null,
            'audit_created_at' => $audit->created_at->toDateTimeString(),
            'audit_updated_at' => $audit->updated_at->toDateTimeString(),
            'user_id'          => '1',
            'user_type'        => User::class,
            'user_is_admin'    => '1',
            'user_first_name'  => 'rick',
            'user_last_name'   => 'Sanchez',
            'user_email'       => 'rick@wubba-lubba-dub.dub',
            'user_created_at'  => $user->created_at->toDateTimeString(),
            'user_updated_at'  => $user->updated_at->toDateTimeString(),
            'new_title'        => 'How To Audit Eloquent Models',
            'new_content'      => 'First step: install the laravel-auditing package.',
            'new_published_at' => $now->toDateTimeString(),
            'new_reviewed'     => 1,
            'new_id'           => 1,
        ], $resolvedData, true);
    }

    /**
     * @group Audit::resolveData
     * @group Audit::getDataValue
     * @test
     */
    public function itReturnsTheAppropriateAuditableDataValues()
    {
        $user = factory(User::class)->create([
            'is_admin'   => 1,
            'first_name' => 'rick',
            'last_name'  => 'Sanchez',
            'email'      => 'rick@wubba-lubba-dub.dub',
        ]);

        $this->actingAs($user);

        $audit = factory(Article::class)->create([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'First step: install the laravel-auditing package.',
            'reviewed'     => 1,
            'published_at' => Carbon::now(),
        ])->audits()->first();

        // Resolve data, making it available to the getDataValue() method
        $this->assertCount(21, $audit->resolveData());

        // Mutate value
        $this->assertSame('HOW TO AUDIT ELOQUENT MODELS', $audit->getDataValue('new_title'));
        $this->assertSame('Rick', $audit->getDataValue('user_first_name'));

        // Cast value
        $this->assertTrue($audit->getDataValue('user_is_admin'));
        $this->assertTrue($audit->getDataValue('new_reviewed'));

        // Date value
        $this->assertInstanceOf(DateTimeInterface::class, $audit->getDataValue('user_created_at'));
        $this->assertInstanceOf(DateTimeInterface::class, $audit->getDataValue('new_published_at'));

        // Original value
        $this->assertSame('First step: install the laravel-auditing package.', $audit->getDataValue('new_content'));
        $this->assertSame('Sanchez', $audit->getDataValue('user_last_name'));

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

        $this->assertCount(10, $metadata = $audit->getMetadata());

        $this->assertArraySubset([
            'audit_id'         => 1,
            'audit_event'      => 'created',
            'audit_url'        => 'console',
            'audit_ip_address' => '127.0.0.1',
            'audit_user_agent' => 'Symfony',
            'audit_tags'       => null,
            'audit_created_at' => $audit->created_at->toDateTimeString(),
            'audit_updated_at' => $audit->updated_at->toDateTimeString(),
            'user_id'          => null,
            'user_type'        => null,
        ], $metadata, true);
    }

    /**
     * @group Audit::getMetadata
     * @test
     */
    public function itReturnsAuditMetadataIncludingUserAttributesAsArray()
    {
        $user = factory(User::class)->create([
            'is_admin'   => 1,
            'first_name' => 'rick',
            'last_name'  => 'Sanchez',
            'email'      => 'rick@wubba-lubba-dub.dub',
        ]);

        $this->actingAs($user);

        $audit = factory(Article::class)->create()->audits()->first();

        $this->assertCount(16, $metadata = $audit->getMetadata());

        $this->assertArraySubset([
            'audit_id'         => 2,
            'audit_event'      => 'created',
            'audit_url'        => 'console',
            'audit_ip_address' => '127.0.0.1',
            'audit_user_agent' => 'Symfony',
            'audit_tags'       => null,
            'audit_created_at' => $audit->created_at->toDateTimeString(),
            'audit_updated_at' => $audit->updated_at->toDateTimeString(),
            'user_id'          => 1,
            'user_type'        => User::class,
            'user_is_admin'    => true,
            'user_first_name'  => 'Rick',
            'user_last_name'   => 'Sanchez',
            'user_email'       => 'rick@wubba-lubba-dub.dub',
            'user_created_at'  => $user->created_at->toDateTimeString(),
            'user_updated_at'  => $user->updated_at->toDateTimeString(),
        ], $metadata, true);
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
    "audit_user_agent": "Symfony",
    "audit_tags": null,
    "audit_created_at": "$audit->created_at",
    "audit_updated_at": "$audit->updated_at",
    "user_id": null,
    "user_type": null
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
            'is_admin'   => 1,
            'first_name' => 'rick',
            'last_name'  => 'Sanchez',
            'email'      => 'rick@wubba-lubba-dub.dub',
        ]);

        $this->actingAs($user);

        $audit = factory(Article::class)->create()->audits()->first();

        $metadata = $audit->getMetadata(true, JSON_PRETTY_PRINT);

        $expected = <<< EOF
{
    "audit_id": 2,
    "audit_event": "created",
    "audit_url": "console",
    "audit_ip_address": "127.0.0.1",
    "audit_user_agent": "Symfony",
    "audit_tags": null,
    "audit_created_at": "$audit->created_at",
    "audit_updated_at": "$audit->updated_at",
    "user_id": 1,
    "user_type": "OwenIt\\\Auditing\\\Tests\\\Models\\\User",
    "user_is_admin": true,
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
        $now = Carbon::now();

        $audit = factory(Article::class)->create([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'First step: install the laravel-auditing package.',
            'reviewed'     => 1,
            'published_at' => $now,
        ])->audits()->first();

        $this->assertCount(5, $modified = $audit->getModified());

        $this->assertArraySubset([
            'title' => [
                'new' => 'HOW TO AUDIT ELOQUENT MODELS',
            ],
            'content' => [
                'new' => 'First step: install the laravel-auditing package.',
            ],
            'published_at' => [
                'new' => $now->toDateTimeString(),
            ],
            'reviewed' => [
                'new' => true,
            ],
            'id' => [
                'new' => 1,
            ],
        ], $modified, true);
    }

    /**
     * @group Audit::getModified
     * @test
     */
    public function itReturnsAuditableModifiedAttributesAsJsonString()
    {
        $now = Carbon::now();

        $audit = factory(Article::class)->create([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'First step: install the laravel-auditing package.',
            'reviewed'     => 1,
            'published_at' => $now,
        ])->audits()->first();

        $modified = $audit->getModified(true, JSON_PRETTY_PRINT);

        $expected = <<< EOF
{
    "title": {
        "new": "HOW TO AUDIT ELOQUENT MODELS"
    },
    "content": {
        "new": "First step: install the laravel-auditing package."
    },
    "published_at": {
        "new": "$now"
    },
    "reviewed": {
        "new": true
    },
    "id": {
        "new": 1
    }
}
EOF;

        $this->assertSame($expected, $modified);
    }

    /**
     * @group Audit::getModified
     * @test
     */
    public function itReturnsDecodedAuditableAttributes()
    {
        $article = new class() extends Article {
            protected $table = 'articles';

            protected $attributeModifiers = [
                'title'   => Base64Encoder::class,
                'content' => LeftRedactor::class,
            ];
        };

        // Audit with redacted/encoded attributes
        $audit = factory(Audit::class)->create([
            'auditable_type' => get_class($article),
            'old_values'     => [
                'title'    => 'SG93IFRvIEF1ZGl0IE1vZGVscw==',
                'content'  => '##A',
                'reviewed' => 0,
            ],
            'new_values'     => [
                'title'    => 'SG93IFRvIEF1ZGl0IEVsb3F1ZW50IE1vZGVscw==',
                'content'  => '############################################kage.',
                'reviewed' => 1,
            ],
        ]);

        $this->assertCount(3, $modified = $audit->getModified());

        $this->assertArraySubset([
            'title' => [
                'new' => 'HOW TO AUDIT ELOQUENT MODELS',
                'old' => 'HOW TO AUDIT MODELS',
            ],
            'content' => [
                'new' => '############################################kage.',
                'old' => '##A',
            ],
            'reviewed' => [
                'new' => true,
                'old' => false,
            ],
        ], $modified, true);
    }

    /**
     * @group Audit::getTags
     * @test
     */
    public function itReturnsTags()
    {
        $audit = factory(Audit::class)->create([
            'tags' => 'foo,bar,baz',
        ]);

        $this->assertIsArray($audit->getTags());
        $this->assertArraySubset([
            'foo',
            'bar',
            'baz',
        ], $audit->getTags(), true);
    }

    /**
     * @group Audit::getTags
     * @test
     */
    public function itReturnsEmptyTags()
    {
        $audit = factory(Audit::class)->create([
            'tags' => null,
        ]);

        $this->assertIsArray($audit->getTags());
        $this->assertEmpty($audit->getTags());
    }
}
