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
