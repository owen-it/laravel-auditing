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
if (!isset($factory)) {
    throw new Exception('Factory is not defined');
}
$factory->define(Audit::class, function (Faker $faker) {
    $morphPrefix = Config::get('audit.user.morph_prefix', 'user');

    return [
        $morphPrefix . '_id' => function () {
            return factory(User::class)->create()->id;
        },
        $morphPrefix . '_type'    => User::class,
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
