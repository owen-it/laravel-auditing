<?php

namespace OwenIt\Auditing\Tests\database\factories;

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
