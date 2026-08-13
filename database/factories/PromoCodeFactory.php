<?php

namespace Database\Factories;

use App\Models\PromoCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromoCode>
 */
class PromoCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->bothify('??####??')),
            'bonus_amount' => $this->faker->randomElement([5, 10, 25, 50]),
            'expires_at' => $this->faker->optional(0.5)->dateTimeBetween('+1 day', '+30 days'),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }
}
