<?php

namespace OwenIt\Auditing\Tests\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use OwenIt\Auditing\Tests\Models\Article;

class ArticleFactory extends Factory
{
    public function definition()
    {
        return [
            'title' => fake()->unique()->sentence,
            'content' => fake()->unique()->paragraph(6),
            'published_at' => null,
            'reviewed' => fake()->randomElement([0, 1]),
        ];
    }

    public function modelName()
    {
        return Article::class;
    }
}
