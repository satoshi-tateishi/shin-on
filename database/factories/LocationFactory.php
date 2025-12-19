<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'sort' => fake()->numberBetween(1, 100),
            'type' => fake()->randomElement(['劇場', '稽古場', '倉庫']),
            'name' => fake()->company().'倉庫',
            'furigana' => fake()->word(),
            'postal_code' => fake()->numerify('###-####'),
            'address' => '東京都'.fake()->city().fake()->streetAddress(),
            'is_active' => true,
            'is_inventory_visible' => true,
            'is_transfer_visible' => true,
            'is_main_warehouse' => false,
        ];
    }

    public function warehouse(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => '倉庫',
        ]);
    }

    public function mainWarehouse(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => '倉庫',
            'is_main_warehouse' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
