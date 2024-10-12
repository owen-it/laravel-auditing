<?php

use Faker\Generator as Faker;
use OwenIt\Auditing\Tests\Models\ApiModel;
use Ramsey\Uuid\Uuid;

/*
|--------------------------------------------------------------------------
| APIModel Factories
|--------------------------------------------------------------------------
|
*/
if (!isset($factory)) {
    throw new Exception('Factory is not defined');
}
$factory->define(ApiModel::class, function (Faker $faker) {
    return [
        'api_model_id' => Uuid::uuid4(),
        'content'      => $faker->unique()->paragraph(6),
        'published_at' => null,
    ];
});
