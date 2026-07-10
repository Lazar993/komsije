<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Widgets\SaasOverviewWidget;
use App\Filament\Widgets\TrialExpiringWidget;
use App\Models\Building;
use App\Models\User;
use App\Services\BuildingHealthScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SaasDashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_widgets_are_super_admin_only(): void
    {
        $manager = User::factory()->create();
        $this->actingAs($manager);
        $this->assertFalse(SaasOverviewWidget::canView());
        $this->assertFalse(TrialExpiringWidget::canView());

        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $this->actingAs($superAdmin);
        $this->assertTrue(SaasOverviewWidget::canView());
        $this->assertTrue(TrialExpiringWidget::canView());
    }

    public function test_overview_widget_renders_status_counts(): void
    {
        Building::factory()->trial()->create();
        Building::factory()->active()->create();
        Building::factory()->suspended()->create();

        $this->actingAs(User::factory()->create(['is_super_admin' => true]));

        Livewire::test(SaasOverviewWidget::class)
            ->assertOk()
            ->assertSee(__('Buildings'))
            ->assertSee(__('Trial'))
            ->assertSee(__('Suspended'));
    }

    public function test_trial_expiring_widget_lists_expiring_buildings(): void
    {
        $expiring = Building::factory()->trial(3)->create(['name' => 'Soon Tower']);
        Building::factory()->trial(20)->create(['name' => 'Later Tower']);

        $this->actingAs(User::factory()->create(['is_super_admin' => true]));

        Livewire::test(TrialExpiringWidget::class)
            ->assertOk()
            ->assertSee('Soon Tower')
            ->assertDontSee('Later Tower');
    }

    public function test_health_score_is_bounded_and_rated(): void
    {
        $building = Building::factory()->trial()->create();

        $result = app(BuildingHealthScoreService::class)->score($building);

        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
        $this->assertContains($result['rating'], [
            __('Excellent'),
            __('Good'),
            __('Needs Attention'),
            __('Inactive'),
        ]);
    }
}
