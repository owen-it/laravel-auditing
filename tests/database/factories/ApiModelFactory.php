<?php

use Faker\Generator as Faker;
use OwenIt\Auditing\Tests\Models\ApiModel;

/*
|--------------------------------------------------------------------------
| Article Factories
|--------------------------------------------------------------------------
|
*/

$factory->define(ApiModel::class, function (Faker $faker) {
    return [
        'api_model_id' => '8a7c2336-705a-41ad-9231-9199b4a64269',
        'content'      => $faker->unique()->paragraph(6),
        'published_at' => null,
    ];
});
