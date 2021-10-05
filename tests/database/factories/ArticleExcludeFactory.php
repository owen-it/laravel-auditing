<?php

use Faker\Generator as Faker;
use OwenIt\Auditing\Tests\Models\ArticleExclude;

/*
|--------------------------------------------------------------------------
| ArticleExclude Factories
|--------------------------------------------------------------------------
|
*/

$factory->define(ArticleExclude::class, function (Faker $faker) {
    return [
        'title'        => $faker->unique()->sentence,
        'content'      => $faker->unique()->paragraph(6),
        'published_at' => null,
        'reviewed'     => $faker->randomElement([0, 1]),
    ];
});
