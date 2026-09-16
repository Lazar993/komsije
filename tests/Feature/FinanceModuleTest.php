<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Enums\ExpenseCategory;
use App\Enums\IncomeCategory;
use App\Filament\Pages\FinancialOverview;
use App\Filament\Resources\Finances\ExpenseResource\Pages\CreateExpense;
use App\Filament\Resources\Finances\IncomeResource;
use App\Filament\Resources\Finances\IncomeResource\Pages\CreateIncome;
use App\Models\Building;
use App\Models\BuildingExpense;
use App\Models\BuildingIncome;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Vendor;
use App\Policies\BuildingExpensePolicy;
use App\Policies\BuildingIncomePolicy;
use App\Policies\VendorPolicy;
use App\Services\FinancialSummaryService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinanceModuleTest extends TestCase
{
    use RefreshDatabase;

    private function manager(Building $building): User
    {
        $manager = User::factory()->create();
        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);

        return $manager;
    }

    private function tenant(Building $building): User
    {
        $tenant = User::factory()->create();
        $building->users()->attach($tenant, ['role' => BuildingRole::Tenant->value]);

        return $tenant;
    }

    private function actingInPanel(User $user): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
    }

    public function test_manager_sees_only_their_buildings_financial_records(): void
    {
        $buildingA = Building::factory()->active()->create();
        $buildingB = Building::factory()->active()->create();
        $manager = $this->manager($buildingA);

        $own = BuildingIncome::factory()->create(['building_id' => $buildingA->getKey()]);
        $foreign = BuildingIncome::factory()->create(['building_id' => $buildingB->getKey()]);

        $this->actingInPanel($manager);

        $visibleIds = IncomeResource::getEloquentQuery()->pluck('id')->all();

        $this->assertContains($own->getKey(), $visibleIds);
        $this->assertNotContains($foreign->getKey(), $visibleIds);
    }

    public function test_manager_cannot_view_another_buildings_records(): void
    {
        $buildingA = Building::factory()->active()->create();
        $buildingB = Building::factory()->active()->create();
        $managerA = $this->manager($buildingA);

        $foreignIncome = BuildingIncome::factory()->create(['building_id' => $buildingB->getKey()]);
        $foreignExpense = BuildingExpense::factory()->create(['building_id' => $buildingB->getKey()]);

        $this->assertFalse((new BuildingIncomePolicy)->view($managerA, $foreignIncome));
        $this->assertFalse((new BuildingExpensePolicy)->view($managerA, $foreignExpense));
    }

    public function test_income_can_be_created(): void
    {
        $building = Building::factory()->active()->create();
        $manager = $this->manager($building);

        $this->actingInPanel($manager);

        Livewire::test(CreateIncome::class)
            ->fillForm([
                'building_id' => $building->getKey(),
                'date' => '2026-09-01',
                'amount' => 120000,
                'category' => IncomeCategory::RegularMaintenance->value,
                'description' => 'Uplata za održavanje',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $income = BuildingIncome::query()->firstWhere('building_id', $building->getKey());

        $this->assertNotNull($income);
        $this->assertSame('120000.00', $income->amount);
        $this->assertSame($manager->getKey(), $income->created_by);
        $this->assertSame($manager->getKey(), $income->updated_by);
    }

    public function test_expense_can_be_created(): void
    {
        $building = Building::factory()->active()->create();
        $manager = $this->manager($building);

        $this->actingInPanel($manager);

        Livewire::test(CreateExpense::class)
            ->fillForm([
                'building_id' => $building->getKey(),
                'date' => '2026-09-05',
                'amount' => 35000,
                'category' => ExpenseCategory::RegularMaintenance->value,
                'description' => 'Popravka lifta',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $expense = BuildingExpense::query()->firstWhere('building_id', $building->getKey());

        $this->assertNotNull($expense);
        $this->assertSame('35000.00', $expense->amount);
        $this->assertSame($manager->getKey(), $expense->created_by);
    }

    public function test_recorded_balance_is_calculated_correctly(): void
    {
        $building = Building::factory()->active()->create();

        BuildingIncome::factory()->create(['building_id' => $building->getKey(), 'amount' => 120000, 'date' => '2026-09-01']);
        BuildingIncome::factory()->create(['building_id' => $building->getKey(), 'amount' => 30000.50, 'date' => '2026-09-02']);
        BuildingExpense::factory()->create(['building_id' => $building->getKey(), 'amount' => 35000, 'date' => '2026-09-03']);

        $totals = app(FinancialSummaryService::class)->totals([$building->getKey()]);

        $this->assertSame('150000.50', $totals['income']);
        $this->assertSame('35000.00', $totals['expense']);
        $this->assertSame('115000.50', $totals['balance']);
    }

    public function test_recorded_balance_only_aggregates_scoped_buildings(): void
    {
        $buildingA = Building::factory()->active()->create();
        $buildingB = Building::factory()->active()->create();

        BuildingIncome::factory()->create(['building_id' => $buildingA->getKey(), 'amount' => 100000]);
        BuildingIncome::factory()->create(['building_id' => $buildingB->getKey(), 'amount' => 999999]);

        $totals = app(FinancialSummaryService::class)->totals([$buildingA->getKey()]);

        $this->assertSame('100000.00', $totals['income']);
    }

    public function test_vendor_can_be_associated_with_an_expense(): void
    {
        $building = Building::factory()->active()->create();
        $manager = $this->manager($building);
        $vendor = Vendor::factory()->create(['building_id' => $building->getKey()]);

        $this->actingInPanel($manager);

        Livewire::test(CreateExpense::class)
            ->fillForm([
                'building_id' => $building->getKey(),
                'date' => '2026-09-05',
                'amount' => 35000,
                'category' => ExpenseCategory::RegularMaintenance->value,
                'description' => 'Popravka lifta',
                'vendor_id' => $vendor->getKey(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $expense = BuildingExpense::query()->firstWhere('building_id', $building->getKey());

        $this->assertNotNull($expense);
        $this->assertSame($vendor->getKey(), $expense->vendor_id);
        $this->assertTrue($vendor->expenses()->whereKey($expense->getKey())->exists());
    }

    public function test_expense_can_be_linked_to_ticket_from_same_building(): void
    {
        $building = Building::factory()->active()->create();
        $manager = $this->manager($building);
        $ticket = Ticket::factory()->create(['building_id' => $building->getKey()]);

        $this->actingInPanel($manager);

        Livewire::test(CreateExpense::class)
            ->fillForm([
                'building_id' => $building->getKey(),
                'date' => '2026-09-05',
                'amount' => 35000,
                'category' => ExpenseCategory::RegularMaintenance->value,
                'description' => 'Popravka lifta',
                'ticket_id' => $ticket->getKey(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $expense = BuildingExpense::query()->firstWhere('building_id', $building->getKey());

        $this->assertNotNull($expense);
        $this->assertSame($ticket->getKey(), $expense->ticket_id);
    }

    public function test_expense_cannot_be_linked_to_ticket_from_another_building(): void
    {
        $building = Building::factory()->active()->create();
        $otherBuilding = Building::factory()->active()->create();
        $manager = $this->manager($building);
        $foreignTicket = Ticket::factory()->create(['building_id' => $otherBuilding->getKey()]);

        $this->actingInPanel($manager);

        Livewire::test(CreateExpense::class)
            ->fillForm([
                'building_id' => $building->getKey(),
                'date' => '2026-09-05',
                'amount' => 35000,
                'category' => ExpenseCategory::RegularMaintenance->value,
                'description' => 'Popravka lifta',
                'ticket_id' => $foreignTicket->getKey(),
            ])
            ->call('create')
            ->assertHasFormErrors(['ticket_id']);

        $this->assertDatabaseMissing('building_expenses', [
            'building_id' => $building->getKey(),
            'ticket_id' => $foreignTicket->getKey(),
        ]);
    }

    public function test_invalid_amount_is_rejected(): void
    {
        $building = Building::factory()->active()->create();
        $manager = $this->manager($building);

        $this->actingInPanel($manager);

        Livewire::test(CreateIncome::class)
            ->fillForm([
                'building_id' => $building->getKey(),
                'date' => '2026-09-01',
                'amount' => 0,
                'category' => IncomeCategory::RegularMaintenance->value,
                'description' => 'Neispravan iznos',
            ])
            ->call('create')
            ->assertHasFormErrors(['amount']);
    }

    public function test_tenant_cannot_access_the_financial_module(): void
    {
        $building = Building::factory()->active()->create();
        $tenant = $this->tenant($building);

        $this->assertFalse((new BuildingIncomePolicy)->viewAny($tenant));
        $this->assertFalse((new BuildingExpensePolicy)->viewAny($tenant));
        $this->assertFalse((new VendorPolicy)->viewAny($tenant));

        $this->actingInPanel($tenant);
        $this->assertFalse(FinancialOverview::canAccess());
    }

    public function test_manager_can_access_the_financial_overview(): void
    {
        $building = Building::factory()->active()->create();
        $manager = $this->manager($building);

        $this->actingInPanel($manager);

        $this->assertTrue(FinancialOverview::canAccess());
    }

    public function test_unauthorized_manager_cannot_manipulate_foreign_records(): void
    {
        $buildingA = Building::factory()->active()->create();
        $buildingB = Building::factory()->active()->create();
        $managerA = $this->manager($buildingA);

        $foreignIncome = BuildingIncome::factory()->create(['building_id' => $buildingB->getKey()]);
        $foreignExpense = BuildingExpense::factory()->create(['building_id' => $buildingB->getKey()]);

        $this->assertFalse((new BuildingIncomePolicy)->update($managerA, $foreignIncome));
        $this->assertFalse((new BuildingIncomePolicy)->delete($managerA, $foreignIncome));
        $this->assertFalse((new BuildingExpensePolicy)->update($managerA, $foreignExpense));
        $this->assertFalse((new BuildingExpensePolicy)->delete($managerA, $foreignExpense));
    }

    public function test_manager_can_manage_their_own_records(): void
    {
        $building = Building::factory()->active()->create();
        $manager = $this->manager($building);

        $income = BuildingIncome::factory()->create(['building_id' => $building->getKey()]);

        $this->assertTrue((new BuildingIncomePolicy)->view($manager, $income));
        $this->assertTrue((new BuildingIncomePolicy)->update($manager, $income));
        $this->assertTrue((new BuildingIncomePolicy)->delete($manager, $income));
    }
}
