<?php

namespace Database\Factories\OwenIt\Auditing\Tests\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use OwenIt\Auditing\Tests\Models\ApiModel;

class ApiModelFactory extends Factory
{
    protected $model = ApiModel::class;

    public function definition()
    {
        return [
            'api_model_id' => fake()->uuid(),
            'content' => fake()->unique()->paragraph(6),
            'published_at' => null,
        ];
    }
}
