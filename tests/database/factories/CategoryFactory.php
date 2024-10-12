<?php

use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Article Factories
|--------------------------------------------------------------------------
|
*/
if (!isset($factory)) {
    throw new Exception('Factory is not defined');
}
$factory->define(\OwenIt\Auditing\Tests\Models\Category::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->colorName(),
    ];
});
