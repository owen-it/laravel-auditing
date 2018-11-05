<?php

use Faker\Generator as Faker;
use OwenIt\Auditing\Models\Audit;
use OwenIt\Auditing\Tests\Models\Article;
use OwenIt\Auditing\Tests\Models\User;

/*
|--------------------------------------------------------------------------
| Audit Factories
|--------------------------------------------------------------------------
|
*/

$factory->define(Audit::class, function (Faker $faker) {
    return [
        'user_id' => function () {
            return factory(User::class)->create()->id;
        },
        'user_type'    => User::class,
        'event'        => 'updated',
        'auditable_id' => function () {
            return factory(Article::class)->create()->id;
        },
        'auditable_type' => Article::class,
        'old_values'     => [],
        'new_values'     => [],
        'url'            => $faker->url,
        'ip_address'     => $faker->ipv4,
        'user_agent'     => $faker->userAgent,
        'tags'           => implode(',', $faker->words(4)),
    ];
});
