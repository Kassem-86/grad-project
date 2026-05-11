<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *  
     * @return array<string, mixed>
     */
    public function definition(): array
    {
            return [
        'first_name' => fake()->firstName(),
        'last_name' => fake()->lastName(),
        'email' => fake()->unique()->safeEmail(),
        'phone' => fake()->numerify('01#########'),
        'gender' => fake()->randomElement(['Male', 'Female']),
        'birthDate' => fake()->dateTimeBetween('-60 years', '-10 years')->format('Y-m-d'),
        'diabetes_type' => fake()->randomElement(User::DIABETES_TYPES),
        'insulin_therapy' => fake()->randomElement(['Pen / Syringes', 'pump', 'No insulin']),
        'weight' => fake()->randomFloat(2, 50, 120),
        'height' => fake()->randomFloat(2, 140, 200),
        'email_verified_at' => now(),
        'password' => static::$password ??= Hash::make('password'), // الباسورد هيبقى كلمة password
        // 'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    // public function unverified(): static
    // {
    //     return $this->state(fn (array $attributes) => [
    //         'email_verified_at' => null,
    //     ]);
    // }
}