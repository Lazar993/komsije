<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BuildingJoinRequestStatus;
use App\Models\Building;
use App\Models\BuildingJoinRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BuildingJoinRequest>
 */
class BuildingJoinRequestFactory extends Factory
{
    protected $model = BuildingJoinRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'building_id' => Building::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'apartment_number' => (string) fake()->numberBetween(1, 80),
            'status' => BuildingJoinRequestStatus::Pending,
            'approved_by' => null,
            'approved_at' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'manager_reminded_at' => null,
            'request_ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
