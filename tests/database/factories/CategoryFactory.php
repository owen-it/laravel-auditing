<?php

namespace Database\Factories\OwenIt\Auditing\Tests\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use OwenIt\Auditing\Tests\Models\Category;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition()
    {
        return [
            'name' => fake()->unique()->colorName(),
        ];
    }
}
