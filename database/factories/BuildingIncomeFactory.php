<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\IncomeCategory;
use App\Enums\PaymentMethod;
use App\Models\Building;
use App\Models\BuildingIncome;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BuildingIncome>
 */
class BuildingIncomeFactory extends Factory
{
    protected $model = BuildingIncome::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'building_id' => Building::factory(),
            'date' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'amount' => fake()->randomFloat(2, 1000, 200000),
            'category' => fake()->randomElement(IncomeCategory::cases()),
            'description' => fake()->sentence(4),
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'note' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
