<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Enums\ExpenseCategory;
use App\Filament\Pages\FinancialOverview;
use App\Models\Building;
use App\Models\BuildingExpense;
use App\Models\BuildingIncome;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Finance\FinancialReportBuilder;
use App\Services\Finance\FinancialReportPdfService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class FinancialReportPdfTest extends TestCase
{
    use RefreshDatabase;

    private function manager(Building $building): User
    {
        $manager = User::factory()->create();
        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);

        return $manager;
    }

    private function actingInPanel(User $user): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
    }

    private function builder(): FinancialReportBuilder
    {
        return app(FinancialReportBuilder::class);
    }

    public function test_report_data_contains_correct_totals_and_balance(): void
    {
        $building = Building::factory()->active()->create();

        BuildingIncome::factory()->create(['building_id' => $building->getKey(), 'amount' => 120000, 'date' => '2026-09-10']);
        BuildingIncome::factory()->create(['building_id' => $building->getKey(), 'amount' => 500.50, 'date' => '2026-09-12']);
        BuildingExpense::factory()->create(['building_id' => $building->getKey(), 'amount' => 35000, 'date' => '2026-09-15']);

        $data = $this->builder()->build($building, Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'));

        $this->assertSame('120500.50', $data['totals']['income']);
        $this->assertSame('35000.00', $data['totals']['expense']);
        $this->assertSame('85500.50', $data['totals']['balance']);
        $this->assertTrue($data['hasTransactions']);
    }

    public function test_report_only_includes_transactions_within_range(): void
    {
        $building = Building::factory()->active()->create();

        BuildingIncome::factory()->create(['building_id' => $building->getKey(), 'amount' => 1000, 'date' => '2026-09-15']);
        BuildingIncome::factory()->create(['building_id' => $building->getKey(), 'amount' => 9999, 'date' => '2026-08-31']);
        BuildingIncome::factory()->create(['building_id' => $building->getKey(), 'amount' => 8888, 'date' => '2026-10-01']);

        $data = $this->builder()->build($building, Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'));

        $this->assertSame('1000.00', $data['totals']['income']);
        $this->assertCount(1, $data['incomes']);
    }

    public function test_report_is_scoped_to_the_selected_building(): void
    {
        $buildingA = Building::factory()->active()->create();
        $buildingB = Building::factory()->active()->create();

        BuildingIncome::factory()->create(['building_id' => $buildingA->getKey(), 'amount' => 1000, 'date' => '2026-09-10']);
        BuildingIncome::factory()->create(['building_id' => $buildingB->getKey(), 'amount' => 5000, 'date' => '2026-09-10']);
        BuildingExpense::factory()->create(['building_id' => $buildingB->getKey(), 'amount' => 2000, 'date' => '2026-09-10']);

        $data = $this->builder()->build($buildingA, Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'));

        $this->assertSame('1000.00', $data['totals']['income']);
        $this->assertSame('0.00', $data['totals']['expense']);
        $this->assertCount(1, $data['incomes']);
        $this->assertCount(0, $data['expenses']);
    }

    public function test_expenses_are_grouped_by_category(): void
    {
        $building = Building::factory()->active()->create();

        BuildingExpense::factory()->create(['building_id' => $building->getKey(), 'category' => ExpenseCategory::RegularMaintenance, 'amount' => 10000, 'date' => '2026-09-05']);
        BuildingExpense::factory()->create(['building_id' => $building->getKey(), 'category' => ExpenseCategory::RegularMaintenance, 'amount' => 5000, 'date' => '2026-09-06']);
        BuildingExpense::factory()->create(['building_id' => $building->getKey(), 'category' => ExpenseCategory::EmergencyIntervention, 'amount' => 7000, 'date' => '2026-09-07']);

        $data = $this->builder()->build($building, Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'));

        $this->assertSame('15000.00', $data['expenseByCategory'][ExpenseCategory::RegularMaintenance->label()]);
        $this->assertSame('7000.00', $data['expenseByCategory'][ExpenseCategory::EmergencyIntervention->label()]);
    }

    public function test_vendor_and_ticket_are_included_when_available(): void
    {
        $building = Building::factory()->active()->create();
        $vendor = Vendor::factory()->create(['building_id' => $building->getKey(), 'name' => 'Lift Servis d.o.o.']);
        $ticket = Ticket::factory()->create(['building_id' => $building->getKey(), 'title' => 'Servis lifta']);

        BuildingExpense::factory()->create([
            'building_id' => $building->getKey(),
            'vendor_id' => $vendor->getKey(),
            'ticket_id' => $ticket->getKey(),
            'invoice_number' => 'INV-123',
            'amount' => 35000,
            'date' => '2026-09-05',
        ]);

        $data = $this->builder()->build($building, Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'));

        $expense = $data['expenses'][0];
        $this->assertSame('Lift Servis d.o.o.', $expense['vendor']);
        $this->assertSame('INV-123', $expense['invoice']);
        $this->assertSame($ticket->getKey(), $expense['ticket']['id']);
        $this->assertSame('Servis lifta', $expense['ticket']['title']);
    }

    public function test_empty_period_produces_valid_report_data(): void
    {
        $building = Building::factory()->active()->create();

        $data = $this->builder()->build($building, Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'));

        $this->assertFalse($data['hasTransactions']);
        $this->assertSame('0.00', $data['totals']['income']);
        $this->assertSame('0.00', $data['totals']['expense']);
        $this->assertSame('0.00', $data['totals']['balance']);
    }

    public function test_pdf_service_generates_a_valid_pdf_with_serbian_content(): void
    {
        $building = Building::factory()->active()->create(['name' => 'Zgrada Čačak Šid Žiža Đon Ćup']);
        BuildingIncome::factory()->create(['building_id' => $building->getKey(), 'amount' => 1000, 'date' => '2026-09-10']);

        $pdf = app(FinancialReportPdfService::class)->pdf($building, Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'));

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }

    public function test_filename_is_sanitized_and_descriptive(): void
    {
        $building = Building::factory()->active()->create(['name' => 'Marka Oreškovića 22/../etc']);

        $filename = app(FinancialReportPdfService::class)
            ->filename($building, Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'));

        $this->assertStringStartsWith('finansijski-izvestaj-', $filename);
        $this->assertStringEndsWith('-2026-09-01-2026-09-30.pdf', $filename);
        $this->assertStringNotContainsString('/', $filename);
        $this->assertStringNotContainsString('..', $filename);
    }

    public function test_authorized_manager_can_export_report(): void
    {
        $building = Building::factory()->active()->create();
        $manager = $this->manager($building);
        BuildingIncome::factory()->create(['building_id' => $building->getKey(), 'amount' => 1000, 'date' => '2026-09-10']);

        $this->actingInPanel($manager);

        Livewire::test(FinancialOverview::class)
            ->callAction('exportPdf', data: [
                'building_id' => $building->getKey(),
                'from' => '2026-09-01',
                'until' => '2026-09-30',
            ])
            ->assertHasNoActionErrors()
            ->assertFileDownloaded();
    }

    public function test_manager_cannot_export_report_for_another_building(): void
    {
        $ownBuilding = Building::factory()->active()->create();
        $secondBuilding = Building::factory()->active()->create();
        $foreignBuilding = Building::factory()->active()->create();
        $manager = $this->manager($ownBuilding);
        // A second managed building enables the building selector (backend guard still applies).
        $secondBuilding->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);
        BuildingIncome::factory()->create(['building_id' => $foreignBuilding->getKey(), 'amount' => 9999, 'date' => '2026-09-10']);

        $this->actingInPanel($manager);

        Livewire::test(FinancialOverview::class)
            ->callAction('exportPdf', data: [
                'building_id' => $foreignBuilding->getKey(),
                'from' => '2026-09-01',
                'until' => '2026-09-30',
            ])
            ->assertHasActionErrors(['building_id'])
            ->assertNoFileDownloaded();
    }

    public function test_backend_guard_blocks_unauthorized_building_export(): void
    {
        $ownBuilding = Building::factory()->active()->create();
        $foreignBuilding = Building::factory()->active()->create();
        $manager = $this->manager($ownBuilding);

        $this->actingAs($manager);

        // The backend guard is defense-in-depth beyond the form's option list.
        $this->assertFalse($manager->isBuildingAdmin((int) $foreignBuilding->getKey()));
        $this->assertTrue($manager->isBuildingAdmin((int) $ownBuilding->getKey()));
    }

    public function test_end_date_before_start_date_is_rejected(): void
    {
        $building = Building::factory()->active()->create();
        $manager = $this->manager($building);

        $this->actingInPanel($manager);

        Livewire::test(FinancialOverview::class)
            ->callAction('exportPdf', data: [
                'building_id' => $building->getKey(),
                'from' => '2026-09-30',
                'until' => '2026-09-01',
            ])
            ->assertHasActionErrors(['until']);
    }
}
