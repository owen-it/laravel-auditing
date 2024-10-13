<?php

namespace OwenIt\Auditing\Tests\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use OwenIt\Auditing\Tests\Models\User;
/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'is_admin'   => fake()->randomElement([0, 1]),
            'first_name' => fake()->firstName,
            'last_name'  => fake()->lastName,
            'email'      => fake()->unique()->safeEmail,
        ];
    }
}
