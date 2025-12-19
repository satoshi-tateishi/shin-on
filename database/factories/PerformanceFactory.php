<?php

namespace Database\Factories;

use App\Models\Performance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Performance>
 */
class PerformanceFactory extends Factory
{
    protected $model = Performance::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'short_name' => fake()->word(),
            'performance_type' => fake()->randomElement(['演劇', 'ミュージカル', 'コンサート', 'イベント']),
            'director' => fake()->name(),
            'status' => 'planning',
            'note' => fake()->optional()->paragraph(),
            'is_active' => true,
        ];
    }

    public function planning(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'planning',
        ]);
    }

    public function preparation(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'preparation',
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
