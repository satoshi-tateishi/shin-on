<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\Phase;
use App\Models\PhaseEquipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PhaseEquipment>
 */
class PhaseEquipmentFactory extends Factory
{
    protected $model = PhaseEquipment::class;

    public function definition(): array
    {
        return [
            'phase_id' => Phase::factory(),
            'equipment_id' => Equipment::factory(),
            'quantity' => 1,
            'status' => 'reserved',
            'note' => fake()->optional()->sentence(),
        ];
    }

    public function reserved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'reserved',
        ]);
    }

    public function checkedOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'checked_out',
            'checkout_date' => now(),
            'checkout_user_id' => User::factory(),
        ]);
    }

    public function checkedIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'checked_in',
            'checkout_date' => now()->subDays(7),
            'checkout_user_id' => User::factory(),
            'checkin_date' => now(),
            'checkin_user_id' => User::factory(),
        ]);
    }

    public function forPhase(Phase $phase): static
    {
        return $this->state(fn (array $attributes) => [
            'phase_id' => $phase->id,
        ]);
    }

    public function forEquipment(Equipment $equipment): static
    {
        return $this->state(fn (array $attributes) => [
            'equipment_id' => $equipment->id,
        ]);
    }

    public function withQuantity(int $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
        ]);
    }
}
