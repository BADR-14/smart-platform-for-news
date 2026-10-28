<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 month', '+2 months');
        $endDate = (clone $startDate)->modify('+'.$this->faker->numberBetween(1, 14).' days');

        return [
            'account_id' => Account::factory()->verified(),
            'category_id' => Category::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(5),
            'post_type' => $this->faker->randomElement(['lecture', 'exhibition', 'event', 'course']),
            'status' => 'published',
            'is_online' => true,
            'location' => null,
            'latitude' => null,
            'longitude' => null,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => 'published']);
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }

    public function onsite(): static
    {
        return $this->state(fn () => [
            'is_online' => false,
            'location' => $this->faker->address(),
            'latitude' => $this->faker->latitude(32, 37),
            'longitude' => $this->faker->longitude(35, 42),
        ]);
    }
}
