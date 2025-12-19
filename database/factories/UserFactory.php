<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sort' => fake()->numberBetween(0, 100),
            'name' => fake()->name(),
            'furigana' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'lineworks_id' => null,
            'icon' => null,
            'mobile_phone' => '090-'.fake()->numerify('####-####'),
            'is_active' => true,
            'postal_code' => fake()->numerify('###-####'),
            'hired_at' => fake()->date(),
            'resigned_at' => null,
            'birthday' => fake()->date(),
            'address' => '東京都'.fake()->city().fake()->streetAddress(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => '090-'.fake()->numerify('####-####'),
            'notes' => fake()->text(100),
            'is_designer' => fake()->boolean(),
            'is_staff' => fake()->boolean(),
            'is_driver' => fake()->boolean(),
            'is_on_leave' => false,
            'is_resigned' => false,
            'role' => fake()->randomElement(['admin', 'editor', 'general', 'viewer']),
            'affiliation' => fake()->randomElement(['employee', 'partner']),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
