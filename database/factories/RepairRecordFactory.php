<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\RepairRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepairRecord>
 */
class RepairRecordFactory extends Factory
{
    protected $model = RepairRecord::class;

    public function definition(): array
    {
        return [
            'equipment_id' => Equipment::factory(),
            'failure_occurred_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'staff_user_id' => User::factory()->state(['is_staff' => true]),
            'performance_name' => fake()->optional()->sentence(3),
            'usage_location' => fake()->optional()->city(),
            'photos' => [],
            'problem_description' => fake()->paragraph(),
            'repair_description' => null,
            'repair_cost' => null,
            'repair_company' => null,
            'reported_by' => User::factory(),
            'repaired_by' => null,
            'reported_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'started_at' => null,
            'completed_at' => null,
            'status' => 'reported',
            'warranty_until' => null,
            'note' => fake()->optional()->sentence(),
        ];
    }

    public function reported(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'reported',
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'started_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'repair_company' => fake()->company(),
            'repaired_by' => fake()->name(),
        ]);
    }

    public function completed(): static
    {
        $startedAt = fake()->dateTimeBetween('-1 month', '-1 week');

        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => $startedAt,
            'completed_at' => fake()->dateTimeBetween($startedAt, 'now'),
            'repair_description' => fake()->paragraph(),
            'repair_cost' => fake()->randomFloat(2, 5000, 100000),
            'repair_company' => fake()->company(),
            'repaired_by' => fake()->name(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'note' => 'キャンセル理由: '.fake()->sentence(),
        ]);
    }
}
