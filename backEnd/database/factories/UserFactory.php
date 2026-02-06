<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
        $role = fake()->randomElement(['admin', 'agent', 'vendeur', 'promoteur']);

        return [
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => $role,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->optional()->numerify('+2126########'),
            'company_name' => in_array($role, ['agent', 'promoteur'], true) ? fake()->company() : null,
            'is_verified' => true,
            'is_active' => true,
            'last_login' => null,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => false,
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => 'admin',
            'company_name' => null,
        ]);
    }

    public function agent(): static
    {
        return $this->state(fn () => [
            'role' => 'agent',
            'company_name' => fake()->company(),
        ]);
    }

    public function vendeur(): static
    {
        return $this->state(fn () => [
            'role' => 'vendeur',
            'company_name' => null,
        ]);
    }

    public function promoteur(): static
    {
        return $this->state(fn () => [
            'role' => 'promoteur',
            'company_name' => fake()->company(),
        ]);
    }
}
