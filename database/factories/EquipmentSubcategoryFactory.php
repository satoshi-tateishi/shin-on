<?php

namespace Database\Factories;

use App\Models\EquipmentCategory;
use App\Models\EquipmentSubcategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentSubcategory>
 */
class EquipmentSubcategoryFactory extends Factory
{
    protected $model = EquipmentSubcategory::class;

    public function definition(): array
    {
        return [
            'category_id' => EquipmentCategory::factory(),
            'sort' => fake()->numberBetween(1, 100),
            'name' => fake()->unique()->word().'サブ',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
