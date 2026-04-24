<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Apartment;
use App\Models\Building;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Apartment>
 */
class ApartmentFactory extends Factory
{
    protected $model = Apartment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'building_id' => Building::factory(),
            'number' => (string) fake()->numberBetween(1, 60),
            'floor' => (string) fake()->numberBetween(0, 12),
            'available_for_marketplace' => false,
            'marketplace_listing_reference' => null,
        ];
    }
}