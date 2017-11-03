<?php

use Faker\Generator as Faker;
use OwenIt\Auditing\Tests\Models\User;

/*
|--------------------------------------------------------------------------
| User Factories
|--------------------------------------------------------------------------
|
*/

$factory->define(User::class, function (Faker $faker) {
    return [
        'first_name' => $faker->firstName,
        'last_name'  => $faker->lastName,
        'email'      => $faker->unique()->safeEmail,
    ];
});
