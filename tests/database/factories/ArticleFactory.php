<?php
/**
 * This file is part of the Laravel Auditing package.
 *
 * @author     Antério Vieira <anteriovieira@gmail.com>
 * @author     Quetzy Garcia  <quetzyg@altek.org>
 * @author     Raphael França <raphaelfrancabsb@gmail.com>
 * @copyright  2015-2017
 *
 * For the full copyright and license information,
 * please view the LICENSE.md file that was distributed
 * with this source code.
 */
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
