<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Models\Announcement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_tickets_index_uses_ajax_pagination_with_ten_items_per_page(): void
    {
        [$manager, $building] = $this->createManagerAndBuilding();
        $apartment = Apartment::factory()->create(['building_id' => $building->getKey()]);

        Ticket::factory()->count(11)->create([
            'building_id' => $building->getKey(),
            'apartment_id' => $apartment->getKey(),
            'reported_by' => $manager->getKey(),
        ]);

        $response = $this->actingAs($manager)
            ->withSession(['current_building_id' => $building->getKey()])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('portal.tickets.index', ['page' => 2]));

        $response->assertOk()->assertViewIs('portal.tickets.partials.results');

        $tickets = $response->viewData('tickets');

        $this->assertSame(10, $tickets->perPage());
        $this->assertSame(11, $tickets->total());
        $this->assertSame(2, $tickets->currentPage());
        $this->assertCount(1, $tickets->items());
    }

    public function test_portal_announcements_index_uses_ajax_pagination_with_ten_items_per_page(): void
    {
        [$manager, $building] = $this->createManagerAndBuilding();

        Announcement::factory()->count(11)->create([
            'building_id' => $building->getKey(),
            'author_id' => $manager->getKey(),
            'published_at' => now(),
        ]);

        $response = $this->actingAs($manager)
            ->withSession(['current_building_id' => $building->getKey()])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('portal.announcements.index', ['page' => 2]));

        $response->assertOk()->assertViewIs('portal.announcements.partials.results');

        $announcements = $response->viewData('announcements');

        $this->assertSame(10, $announcements->perPage());
        $this->assertSame(11, $announcements->total());
        $this->assertSame(2, $announcements->currentPage());
        $this->assertCount(1, $announcements->items());
    }

    /**
     * @return array{0: User, 1: Building}
     */
    private function createManagerAndBuilding(): array
    {
        $manager = User::factory()->create();
        $building = Building::factory()->create();

        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);

        return [$manager, $building];
    }
}