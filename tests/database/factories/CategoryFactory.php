<?php

namespace OwenIt\Auditing\Tests\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use OwenIt\Auditing\Tests\Models\Category;

class CategoryFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => fake()->unique()->colorName(),
        ];
    }

    public function modelName()
    {
        return Category::class;
    }
}
