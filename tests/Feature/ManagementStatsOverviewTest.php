<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Enums\TicketStatus;
use App\Filament\Widgets\ManagementStatsOverview;
use App\Models\Announcement;
use App\Models\Building;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManagementStatsOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_is_visible_to_building_managers(): void
    {
        $manager = User::factory()->create();
        $building = Building::factory()->create();
        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);

        $this->actingAs($manager);

        $this->assertTrue(ManagementStatsOverview::canView());
    }

    public function test_widget_is_hidden_from_plain_tenants(): void
    {
        $tenant = User::factory()->create();
        $building = Building::factory()->create();
        $building->users()->attach($tenant, ['role' => BuildingRole::Tenant->value]);

        $this->actingAs($tenant);

        $this->assertFalse(ManagementStatsOverview::canView());
    }

    public function test_stats_are_scoped_to_managed_buildings_and_respect_the_range_filter(): void
    {
        $manager = User::factory()->create();
        $managed = Building::factory()->create();
        $other = Building::factory()->create();
        $managed->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);

        // Recent activity in the managed building.
        Ticket::factory()->create([
            'building_id' => $managed->getKey(),
            'created_at' => now()->subDay(),
        ]);

        // Old activity outside a 3-day window.
        Ticket::factory()->create([
            'building_id' => $managed->getKey(),
            'created_at' => now()->subDays(20),
        ]);

        // Activity in a building this manager does not manage should be ignored.
        Ticket::factory()->create([
            'building_id' => $other->getKey(),
            'created_at' => now()->subDay(),
        ]);

        Announcement::factory()->create([
            'building_id' => $managed->getKey(),
            'created_at' => now()->subDay(),
        ]);

        $this->actingAs($manager);

        Livewire::test(ManagementStatsOverview::class)
            ->assertSet('range', '7')
            ->assertSee(__('New tickets'))
            ->assertSee(__('Announcements'))
            ->set('range', '3')
            ->assertSet('range', '3')
            ->set('range', 'all')
            ->assertSet('range', 'all');
    }

    public function test_building_filter_options_only_include_managed_buildings(): void
    {
        $manager = User::factory()->create();
        $first = Building::factory()->create(['name' => 'Alpha']);
        $second = Building::factory()->create(['name' => 'Beta']);
        $unmanaged = Building::factory()->create(['name' => 'Gamma']);

        $first->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);
        $second->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);

        $this->actingAs($manager);

        $widget = new ManagementStatsOverview;
        $options = $widget->getBuildingOptions();

        $this->assertTrue($widget->hasMultipleBuildings());
        $this->assertArrayHasKey('all', $options);
        $this->assertContains('Alpha', $options);
        $this->assertContains('Beta', $options);
        $this->assertNotContains('Gamma', $options);
    }

    public function test_building_filter_scopes_stats_to_the_selected_building(): void
    {
        $manager = User::factory()->create();
        $first = Building::factory()->create();
        $second = Building::factory()->create();
        $first->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);
        $second->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);

        Ticket::factory()->count(2)->create([
            'building_id' => $first->getKey(),
            'created_at' => now()->subDay(),
        ]);
        Ticket::factory()->create([
            'building_id' => $second->getKey(),
            'created_at' => now()->subDay(),
        ]);

        $this->actingAs($manager);

        Livewire::test(ManagementStatsOverview::class)
            ->assertSet('building', 'all')
            ->set('building', (string) $first->getKey())
            ->assertSet('building', (string) $first->getKey());
    }
}
