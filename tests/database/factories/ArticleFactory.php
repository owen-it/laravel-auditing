<?php

namespace OwenIt\Auditing\Tests\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use OwenIt\Auditing\Tests\Models\Article;
/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [   
                'title'        => fake()->unique()->sentence,
                'content'      => fake()->unique()->paragraph(6),
                'published_at' => null,
                'reviewed'     => fake()->randomElement([0, 1]),
        ];
    }
}
