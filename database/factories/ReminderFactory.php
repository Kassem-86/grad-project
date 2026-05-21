<?php

namespace Database\Factories;

use App\Models\Reminder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reminder>
 */
class ReminderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $messageType = fake()->randomElement(['medication', 'glucose_check', 'meal']);
        $medicationNames = ['Insulin Aspart', 'Metformin 500mg', 'Lisinopril 10mg', 'Atorvastatin 20mg', 'Aspirin 81mg'];

        return [
            'user_id' => 1,
            'title' => fake()->sentence(3),
            'message_type' => $messageType,
            'medication_name' => $messageType === 'medication' ? fake()->randomElement($medicationNames) : null,
            'time' => fake()->dateTimeBetween('-1 hour', '+2 hours'),
            'status' => fake()->randomElement(['Still', 'Done', 'Skipped']),
        ];
    }

    /**
     * Indicate that the reminder should be set for the current minute (for testing).
     */
    public function atCurrentMinute(): static
    {
        return $this->state(fn(array $attributes) => [
            'time' => Carbon::now(),
            'status' => 'Still',
        ]);
    }

    /**
     * Indicate that the reminder should be in the past.
     */
    public function inPast(): static
    {
        return $this->state(fn(array $attributes) => [
            'time' => fake()->dateTimeBetween('-2 days', '-1 hour'),
            'status' => fake()->randomElement(['Done', 'Skipped']),
        ]);
    }

    /**
     * Indicate that the reminder should be in the future.
     */
    public function inFuture(): static
    {
        return $this->state(fn(array $attributes) => [
            'time' => fake()->dateTimeBetween('+1 hour', '+7 days'),
            'status' => 'Still',
        ]);
    }
}
