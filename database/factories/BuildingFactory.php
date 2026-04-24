<?php

declare(strict_types=1);

namespace Database\Factories;

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
}