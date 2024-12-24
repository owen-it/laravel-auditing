<?php

namespace OwenIt\Auditing\Tests\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use OwenIt\Auditing\Tests\Models\ApiModel;
use Ramsey\Uuid\Uuid;

class ApiModelFactory extends Factory
{
    public function definition()
    {
        return [
            'api_model_id' => Uuid::uuid4(),
            'content' => fake()->unique()->paragraph(6),
            'published_at' => null,
        ];
    }

    public function modelName()
    {
        return ApiModel::class;
    }
}
