<?php

namespace Database\Factories;

use App\Models\Production;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Production>
 */
class ProductionFactory extends Factory
{
    protected $model = Production::class;

    public function definition(): array
    {
        return [
            'sort' => fake()->numberBetween(1, 100),
            'type' => fake()->randomElement(['株式会社', '有限会社', '合同会社', '財団法人', '公益財団法人', '公益社団法人', 'その他']),
            'name' => fake()->company(),
            'postal_code' => fake()->numerify('###-####'),
            'address' => '東京都'.fake()->city().fake()->streetAddress(),
            'note' => null,
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
