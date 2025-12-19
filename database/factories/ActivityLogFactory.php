<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement([
                'equipment.checkout',
                'equipment.checkin',
                'equipment.transfer',
                'performance.create',
                'performance.update',
                'phase.create',
                'phase.update',
                'user.login',
            ]),
            'subject_type' => Equipment::class,
            'subject_id' => 1,
            'subject_name' => fake()->word().'機材',
            'description' => fake()->sentence(),
            'properties' => null,
            'created_at' => now(),
        ];
    }

    public function equipmentAction(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => fake()->randomElement([
                'equipment.checkout',
                'equipment.checkin',
                'equipment.transfer',
            ]),
        ]);
    }

    public function performanceAction(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => fake()->randomElement([
                'performance.create',
                'performance.update',
            ]),
        ]);
    }

    public function phaseAction(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => fake()->randomElement([
                'phase.create',
                'phase.update',
            ]),
        ]);
    }

    public function loginAction(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'user.login',
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
