<?php

namespace OwenIt\Auditing\Tests\Unit;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Testing\Assert;
use OwenIt\Auditing\Encoders\Base64Encoder;
use OwenIt\Auditing\Models\Audit;
use OwenIt\Auditing\Redactors\LeftRedactor;
use OwenIt\Auditing\Resolvers\UrlResolver;
use OwenIt\Auditing\Tests\AuditingTestCase;
use OwenIt\Auditing\Tests\Models\Article;
use OwenIt\Auditing\Tests\Models\Money;
use OwenIt\Auditing\Tests\Models\User;

class AuditTest extends AuditingTestCase
{
    /**
     * @group Audit::resolveData
     *
     * @test
     */
    public function it_resolves_audit_data()
    {
        $now = Carbon::now();

        $article = factory(Article::class)->create([
            'title' => 'How To Audit Eloquent Models',
            'content' => 'First step: install the laravel-auditing package.',
            'reviewed' => 1,
            'published_at' => $now,
        ]);

        $audit = $article->audits()->first();
        $this->assertNotNull($audit);

        $resolvedData = $audit->resolveData();
        $this->assertCount(15, $resolvedData);

        Assert::assertArraySubset([
            'audit_id' => 1,
            'audit_event' => 'created',
            'audit_url' => UrlResolver::resolveCommandLine(),
            'audit_ip_address' => '127.0.0.1',
            'audit_user_agent' => 'Symfony',
            'audit_tags' => null,
            'audit_created_at' => $audit->getSerializedDate($audit->created_at),
            'audit_updated_at' => $audit->getSerializedDate($audit->updated_at),
            'user_id' => null,
            'user_type' => null,
            'new_title' => 'How To Audit Eloquent Models',
            'new_content' => Article::contentMutate('First step: install the laravel-auditing package.'),
            'new_published_at' => $now->toDateTimeString(),
            'new_reviewed' => 1,
            'new_id' => 1,
        ], $resolvedData, true);
    }

    /**
     * @group Audit::resolveData
     *
     * @test
     */
    public function it_resolves_audit_data_including_user_attributes()
    {
        $now = Carbon::now();

        $user = factory(User::class)->create([
            'is_admin' => 1,
            'first_name' => 'rick',
            'last_name' => 'Sanchez',
            'email' => 'rick@wubba-lubba-dub.dub',
        ]);

        $this->actingAs($user);

        $article = factory(Article::class)->create([
            'title' => 'How To Audit Eloquent Models',
            'content' => 'First step: install the laravel-auditing package.',
            'reviewed' => 1,
            'published_at' => $now,
        ]);

        $audit = $article->audits()->first();

        $this->assertNotNull($audit);

        $this->assertCount(21, $resolvedData = $audit->resolveData());

        Assert::assertArraySubset([
            'audit_id' => 2,
            'audit_event' => 'created',
            'audit_url' => UrlResolver::resolveCommandLine(),
            'audit_ip_address' => '127.0.0.1',
            'audit_user_agent' => 'Symfony',
            'audit_tags' => null,
            'audit_created_at' => $audit->getSerializedDate($audit->created_at),
            'audit_updated_at' => $audit->getSerializedDate($audit->updated_at),
            'user_type' => User::class,
            'user_first_name' => 'rick',
            'user_last_name' => 'Sanchez',
            'user_email' => 'rick@wubba-lubba-dub.dub',
            'user_created_at' => $user->created_at->toDateTimeString(),
            'user_updated_at' => $user->updated_at->toDateTimeString(),
            'new_title' => 'How To Audit Eloquent Models',
            'new_content' => Article::contentMutate('First step: install the laravel-auditing package.'),
            'new_published_at' => $now->toDateTimeString(),
            'new_reviewed' => 1,
            'new_id' => 1,
        ], $resolvedData, true);
    }

    /**
     * @group Audit::resolveData
     * @group Audit::getDataValue
     *
     * @test
     */
    public function it_returns_the_appropriate_auditable_data_values()
    {
        $user = factory(User::class)->create([
            'is_admin' => 1,
            'first_name' => 'rick',
            'last_name' => 'Sanchez',
            'email' => 'rick@wubba-lubba-dub.dub',
        ]);

        $this->actingAs($user);

        $audit = factory(Article::class)->create([
            'title' => 'How To Audit Eloquent Models',
            'content' => 'First step: install the laravel-auditing package.',
            'reviewed' => 1,
            'published_at' => Carbon::now(),
        ])->audits()->first();

        $this->assertNotNull($audit);

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
        $this->assertSame(Article::contentMutate('First step: install the laravel-auditing package.'), $audit->getDataValue('new_content'));
        $this->assertSame('Sanchez', $audit->getDataValue('user_last_name'));

        // Invalid value
        $this->assertNull($audit->getDataValue('invalid_key'));
    }

    /**
     * @group Audit::resolveData
     * @group Audit::getDataValue
     *
     * @test
     */
    public function it_returns_the_appropriate_auditable_data_values_with_custom_cast_value_object()
    {
        $user = factory(User::class)->create([
            'is_admin' => 1,
            'first_name' => 'rick',
            'last_name' => 'Sanchez',
            'email' => 'rick@wubba-lubba-dub.dub',
        ]);

        $this->actingAs($user);

        $article = factory(Article::class)->create([
            'title' => 'How To Audit Eloquent Models',
            'content' => 'First step: install the laravel-auditing package.',
            'reviewed' => 1,
            'published_at' => Carbon::now(),
            'price' => '12.45',
        ]);

        $article->price = '24.68';
        $article->save();

        $lastAudit = $article->audits()->skip(1)->first();

        $this->assertNotNull($lastAudit);

        $this->assertEquals(new Money('24.68', 'USD'), $lastAudit->getModified()['price']['new']);
        $this->assertEquals(new Money('12.45', 'USD'), $lastAudit->getModified()['price']['old']);
    }

    /**
     * @group Audit::getMetadata
     *
     * @test
     */
    public function it_returns_audit_metadata_as_array()
    {
        $audit = factory(Article::class)->create()->audits()->first();

        $this->assertNotNull($audit);

        $this->assertCount(10, $metadata = $audit->getMetadata());

        Assert::assertArraySubset([
            'audit_id' => 1,
            'audit_event' => 'created',
            'audit_url' => UrlResolver::resolveCommandLine(),
            'audit_ip_address' => '127.0.0.1',
            'audit_user_agent' => 'Symfony',
            'audit_tags' => null,
            'audit_created_at' => $audit->getSerializedDate($audit->created_at),
            'audit_updated_at' => $audit->getSerializedDate($audit->updated_at),
            'user_id' => null,
            'user_type' => null,
        ], $metadata, true);
    }

    /**
     * This test is meant to be run with specific command line "vendor/bin/phpunit tests/Unit/AuditTest.php --group command-line-url-resolver"
     *
     * @group command-line-url-resolver
     *
     * @test
     */
    public function it_returns_proper_command_line_in_url_audit_metadata()
    {
        $audit = factory(Article::class)->create()->audits()->first();

        $this->assertNotNull($audit);

        Assert::assertEquals($audit->getMetadata()['audit_url'], 'vendor/bin/phpunit tests/Unit/AuditTest.php --group command-line-url-resolver');
    }

    /**
     * @group Audit::getMetadata
     *
     * @test
     */
    public function it_returns_audit_metadata_including_user_attributes_as_array()
    {
        $user = factory(User::class)->create([
            'is_admin' => 1,
            'first_name' => 'rick',
            'last_name' => 'Sanchez',
            'email' => 'rick@wubba-lubba-dub.dub',
        ]);

        $this->actingAs($user);

        $audit = factory(Article::class)->create()->audits()->first();

        $this->assertNotNull($audit);

        $this->assertCount(16, $metadata = $audit->getMetadata());

        Assert::assertArraySubset([
            'audit_id' => 2,
            'audit_event' => 'created',
            'audit_url' => UrlResolver::resolveCommandLine(),
            'audit_ip_address' => '127.0.0.1',
            'audit_user_agent' => 'Symfony',
            'audit_tags' => null,
            'audit_created_at' => $audit->getSerializedDate($audit->created_at),
            'audit_updated_at' => $audit->getSerializedDate($audit->updated_at),
            'user_id' => 1,
            'user_type' => User::class,
            'user_is_admin' => true,
            'user_first_name' => 'Rick',
            'user_last_name' => 'Sanchez',
            'user_email' => 'rick@wubba-lubba-dub.dub',
            'user_created_at' => $audit->getSerializedDate($user->created_at),
            'user_updated_at' => $audit->getSerializedDate($user->updated_at),
        ], $metadata, true);
    }

    /**
     * @group Audit::getMetadata
     *
     * @test
     */
    public function it_returns_audit_metadata_as_json_string()
    {
        $audit = factory(Article::class)->create()->audits()->first();

        $this->assertNotNull($audit);

        $metadata = $audit->getMetadata(true, JSON_PRETTY_PRINT);

        $created_at = $audit->getSerializedDate($audit->created_at);
        $updated_at = $audit->getSerializedDate($audit->updated_at);
        $expected = [
            'audit_id' => 1,
            'audit_event' => 'created',
            'audit_tags' => null,
            'audit_created_at' => $created_at,
            'audit_updated_at' => $updated_at,
            'user_id' => null,
            'user_type' => null,
            'audit_url' => UrlResolver::resolveCommandLine(),
            'audit_ip_address' => '127.0.0.1',
            'audit_user_agent' => 'Symfony',
        ];

        $this->assertSame($expected, json_decode($metadata, true));
    }

    /**
     * @group Audit::getMetadata
     *
     * @test
     */
    public function it_returns_audit_metadata_including_user_attributes_as_json_string()
    {
        $user = factory(User::class)->create([
            'is_admin' => 1,
            'first_name' => 'rick',
            'last_name' => 'Sanchez',
            'email' => 'rick@wubba-lubba-dub.dub',
        ]);

        $this->actingAs($user);

        $audit = factory(Article::class)->create()->audits()->first();

        $this->assertNotNull($audit);

        $metadata = $audit->getMetadata(true, JSON_PRETTY_PRINT);

        $created_at = $audit->getSerializedDate($audit->created_at);
        $updated_at = $audit->getSerializedDate($audit->updated_at);
        $user_created_at = $audit->getSerializedDate($user->created_at);
        $user_updated_at = $audit->getSerializedDate($user->updated_at);
        $expected = [
            'audit_id' => 2,
            'audit_event' => 'created',
            'audit_tags' => null,
            'audit_created_at' => $created_at,
            'audit_updated_at' => $updated_at,
            'user_id' => 1,
            'user_type' => 'OwenIt\\Auditing\\Tests\\Models\\User',
            'audit_url' => UrlResolver::resolveCommandLine(),
            'audit_ip_address' => '127.0.0.1',
            'audit_user_agent' => 'Symfony',
            'user_is_admin' => true,
            'user_first_name' => 'Rick',
            'user_last_name' => 'Sanchez',
            'user_email' => 'rick@wubba-lubba-dub.dub',
            'user_created_at' => $user_created_at,
            'user_updated_at' => $user_updated_at,
        ];

        $this->assertSame($expected, json_decode($metadata, true));
    }

    /**
     * @group Audit::getModified
     *
     * @test
     */
    public function it_returns_auditable_modified_attributes_as_array()
    {
        $now = Carbon::now()->second(0)->microsecond(0);

        $audit = factory(Article::class)->create([
            'title' => 'How To Audit Eloquent Models',
            'content' => 'First step: install the laravel-auditing package.',
            'reviewed' => 1,
            'published_at' => $now,
        ])->audits()->first();

        $this->assertNotNull($audit);

        $this->assertCount(5, $modified = $audit->getModified());

        Assert::assertArraySubset([
            'title' => [
                'new' => 'HOW TO AUDIT ELOQUENT MODELS',
            ],
            'content' => [
                'new' => Article::contentMutate('First step: install the laravel-auditing package.'),
            ],
            'published_at' => [
                'new' => $audit->getSerializedDate($now),
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
     *
     * @test
     */
    public function it_returns_auditable_modified_attributes_as_json_string()
    {
        $now = Carbon::now()->second(0)->microsecond(0);

        $audit = factory(Article::class)->create([
            'title' => 'How To Audit Eloquent Models',
            'content' => 'First step: install the laravel-auditing package.',
            'reviewed' => 1,
            'published_at' => $now,
        ])->audits()->first();

        $this->assertNotNull($audit);

        $modified = $audit->getModified(true, JSON_PRETTY_PRINT);

        $serializedDate = $audit->getSerializedDate($now);
        $expected = [
            'title' => [
                'new' => 'HOW TO AUDIT ELOQUENT MODELS',
            ],
            'content' => [
                'new' => Article::contentMutate('First step: install the laravel-auditing package.'),
            ],
            'published_at' => [
                'new' => "$serializedDate",
            ],
            'reviewed' => [
                'new' => true,
            ],
            'id' => [
                'new' => 1,
            ],
        ];

        $this->assertSame($expected, json_decode($modified, true));
    }

    /**
     * @group Audit::getModified
     *
     * @test
     */
    public function it_returns_decoded_auditable_attributes()
    {
        $model = factory(Article::class)->create();

        $this->assertTrue(itReturnsDecodedAuditableAttributesArticle::first()->is($model));

        // Audit with redacted/encoded attributes
        $audit = Audit::create([
            'event' => 'updated',
            'auditable_id' => $model->getKey(),
            'auditable_type' => itReturnsDecodedAuditableAttributesArticle::class,
            'old_values' => [
                'title' => 'SG93IFRvIEF1ZGl0IE1vZGVscw==',
                'content' => '##A',
                'reviewed' => 0,
            ],
            'new_values' => [
                'title' => 'SG93IFRvIEF1ZGl0IEVsb3F1ZW50IE1vZGVscw==',
                'content' => '############################################kage.',
                'reviewed' => 1,
            ],
        ]);

        $this->assertCount(3, $modified = $audit->getModified());

        Assert::assertArraySubset([
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
     *
     * @test
     */
    public function it_returns_tags()
    {
        $model = factory(Article::class)->create();

        $audit = Audit::create([
            'event' => 'updated',
            'auditable_id' => $model->getKey(),
            'auditable_type' => Article::class,
            'tags' => 'foo,bar,baz',
        ]);

        $this->assertIsArray($audit->getTags());
        Assert::assertArraySubset([
            'foo',
            'bar',
            'baz',
        ], $audit->getTags(), true);
    }

    /**
     * @group Audit::getTags
     *
     * @test
     */
    public function it_returns_empty_tags()
    {
        $model = factory(Article::class)->create();

        $audit = Audit::create([
            'event' => 'updated',
            'auditable_id' => $model->getKey(),
            'auditable_type' => Article::class,
            'tags' => null,
        ]);

        $this->assertIsArray($audit->getTags());
        $this->assertEmpty($audit->getTags());
    }
}

class itReturnsDecodedAuditableAttributesArticle extends Article
{
    protected $table = 'articles';

    protected $attributeModifiers = [
        'title' => Base64Encoder::class,
        'content' => LeftRedactor::class,
    ];
}
