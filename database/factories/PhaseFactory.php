<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Performance;
use App\Models\Phase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Phase>
 */
class PhaseFactory extends Factory
{
    protected $model = Phase::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+3 months');
        $endDate = fake()->dateTimeBetween($startDate, '+6 months');

        return [
            'performance_id' => Performance::factory(),
            'location_id' => Location::factory(),
            'name' => fake()->randomElement(['稽古', '仕込み', '本番', 'バラシ']),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'note' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function rehearsal(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => '稽古',
        ]);
    }

    public function setup(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => '仕込み',
        ]);
    }

    public function production(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => '本番',
        ]);
    }

    public function teardown(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'バラシ',
        ]);
    }

    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => fake()->dateTimeBetween('-3 months', '-1 month'),
            'end_date' => fake()->dateTimeBetween('-1 month', '-1 day'),
        ]);
    }

    public function future(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => fake()->dateTimeBetween('+1 day', '+1 month'),
            'end_date' => fake()->dateTimeBetween('+1 month', '+3 months'),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
