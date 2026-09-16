<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Models\Building;
use App\Models\BuildingExpense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BuildingExpense>
 */
class BuildingExpenseFactory extends Factory
{
    protected $model = BuildingExpense::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'building_id' => Building::factory(),
            'date' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'amount' => fake()->randomFloat(2, 1000, 200000),
            'category' => fake()->randomElement(ExpenseCategory::cases()),
            'description' => fake()->sentence(4),
            'vendor_id' => null,
            'ticket_id' => null,
            'invoice_number' => (string) fake()->numerify('INV-####'),
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'note' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
