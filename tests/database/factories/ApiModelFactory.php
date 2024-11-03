<?php

namespace OwenIt\Auditing\Tests\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use OwenIt\Auditing\Tests\Models\ApiModel;
use Illuminate\Support\Str;

/**
 * @extends Factory<ApiModel>
 */
class ApiModelFactory extends Factory
{

    protected $model = ApiModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'api_model_id' => Str::uuid(),
            'content'      => fake()->unique()->paragraph(6),
            'published_at' => null,
        ];
    }
}
