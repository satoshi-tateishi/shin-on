<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\EquipmentSubcategory;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipment>
 */
class EquipmentFactory extends Factory
{
    protected $model = Equipment::class;

    public function definition(): array
    {
        $location = Location::factory();

        return [
            'subcategory_id' => EquipmentSubcategory::factory(),
            'sort' => fake()->numberBetween(1, 100),
            'manufacturer' => fake()->company(),
            'name' => fake()->word().' '.fake()->randomNumber(3),
            'company_number' => 'EQ-'.fake()->unique()->randomNumber(5),
            'management_type' => 'individual',
            'quantity' => 1,
            'unit' => '台',
            'model_number' => 'MDL-'.fake()->randomNumber(4),
            'serial_number' => 'SN-'.fake()->uuid(),
            'supplier' => fake()->company(),
            'purchase_date' => fake()->dateTimeBetween('-3 years', 'now'),
            'warranty_expiry' => fake()->dateTimeBetween('now', '+2 years'),
            'price' => fake()->randomFloat(2, 10000, 1000000),
            'status' => 'available',
            'location_id' => $location,
            'now_location_id' => $location,
            'is_discard' => false,
            'is_schedule_visible' => true,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'available',
            'is_discard' => false,
        ]);
    }

    public function inUse(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_use',
        ]);
    }

    public function underRepair(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'repair',
        ]);
    }

    public function discarded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'retired',
            'is_discard' => true,
            'discard_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function quantityManaged(): static
    {
        return $this->state(fn (array $attributes) => [
            'management_type' => 'quantity',
            'quantity' => fake()->numberBetween(5, 50),
        ]);
    }
}
