<?php
/**
 * This file is part of the Laravel Auditing package.
 *
 * @author     Antério Vieira <anteriovieira@gmail.com>
 * @author     Quetzy Garcia  <quetzyg@altek.org>
 * @author     Raphael França <raphaelfrancabsb@gmail.com>
 * @copyright  2015-2018
 *
 * For the full copyright and license information,
 * please view the LICENSE.md file that was distributed
 * with this source code.
 */
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
        'is_admin'   => $faker->randomElement([0, 1]),
        'first_name' => $faker->firstName,
        'last_name'  => $faker->lastName,
        'email'      => $faker->unique()->safeEmail,
    ];
});
