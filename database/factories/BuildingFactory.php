<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BuildingStatus;
use App\Models\Building;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Building>
 */
class BuildingFactory extends Factory
{
    protected $model = Building::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Residence',
            'address' => fake()->streetAddress(),
            'created_by' => User::factory(),
            'billing_customer_reference' => null,
        ];
    }

    public function trial(int $daysRemaining = 30): static
    {
        return $this->state(fn (): array => [
            'status' => BuildingStatus::Trial,
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays($daysRemaining),
        ]);
    }

    public function trialExpired(): static
    {
        return $this->state(fn (): array => [
            'status' => BuildingStatus::Trial,
            'trial_started_at' => now()->subDays(Building::TRIAL_DAYS + 1),
            'trial_ends_at' => now()->subDay(),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => BuildingStatus::Active,
            'subscription_started_at' => now(),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (): array => [
            'status' => BuildingStatus::Suspended,
            'suspended_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => BuildingStatus::Archived,
            'archived_at' => now(),
        ]);
    }
}