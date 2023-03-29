<?php

namespace OwenIt\Auditing\Tests\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Config;
use OwenIt\Auditing\Tests\Models\Article;
use OwenIt\Auditing\Tests\Models\User;

class AuditFactory extends Factory
{

    public function definition()
    {
        $morphPrefix = Config::get('audit.user.morph_prefix', 'user');

        return [
            $morphPrefix . '_id'   => function () {
                return User::factory()->create()->id;
            },
            $morphPrefix . '_type' => User::class,
            'event'                => 'updated',
            'auditable_id'         => function () {
                return Article::factory()->create()->id;
            },
            'auditable_type'       => Article::class,
            'old_values'           => [],
            'new_values'           => [],
            'url'                  => fake()->url,
            'ip_address'           => fake()->ipv4,
            'user_agent'           => fake()->userAgent,
            'tags'                 => implode(',', fake()->words(4)),
        ];
    }

    public function modelName()
    {
        return \OwenIt\Auditing\Tests\Models\Audit::class;
    }
}
