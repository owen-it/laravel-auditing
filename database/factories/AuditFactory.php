<?php

namespace OwenIt\Auditing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use OwenIt\Auditing\Models\Audit;
use Illuminate\Support\Facades\Config;
use OwenIt\Auditing\Tests\Models\Article;
use OwenIt\Auditing\Tests\Models\User;
/**
 * @extends Factory<Audit>
 */
class AuditFactory extends Factory
{
    protected $model = Audit::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $morphPrefix = Config::get('audit.user.morph_prefix', 'user');

        return [
            $morphPrefix . '_id' => function () {
                return User::factory()->create()->id;
            },
            $morphPrefix . '_type'    => User::class,
            'event'        => 'updated',
            'auditable_id' => function () {
                return Article::factory()->create()->id;
            },
            'auditable_type' => Article::class,
            'old_values'     => [],
            'new_values'     => [],
            'url'            => fake()->url,
            'ip_address'     => fake()->ipv4,
            'user_agent'     => fake()->userAgent,
            'tags'           => implode(',', fake()->words(4)),
        ];
    }
}
