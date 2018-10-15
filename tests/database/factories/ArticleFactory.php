<?php

use Faker\Generator as Faker;
use OwenIt\Auditing\Tests\Models\Article;

/*
|--------------------------------------------------------------------------
| Article Factories
|--------------------------------------------------------------------------
|
*/

$factory->define(Article::class, function (Faker $faker) {
    return [
        'title'        => $faker->unique()->sentence,
        'content'      => $faker->unique()->paragraph(6),
        'published_at' => null,
        'reviewed'     => $faker->randomElement([0, 1]),
    ];
});
