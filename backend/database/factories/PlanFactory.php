<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'باقة '.$this->faker->unique()->numberBetween(1, 9999),
            'posts_count' => 10,
            'price' => 80.00,
            'is_active' => true,
        ];
    }
}
