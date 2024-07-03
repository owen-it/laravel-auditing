<?php

use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Article Factories
|--------------------------------------------------------------------------
|
*/

$factory->define(\OwenIt\Auditing\Tests\Models\Group::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->colorName(),
    ];
});
