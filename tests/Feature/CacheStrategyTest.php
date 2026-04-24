<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Announcement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AnnouncementService;
use App\Services\ApartmentService;
use App\Services\BuildingService;
use App\Services\DashboardService;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CacheStrategyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('cache.default', 'array');
        Cache::flush();
        Notification::fake();
    }

    public function test_dashboard_cache_is_invalidated_when_ticket_is_resolved(): void
    {
        $manager = User::factory()->create();
        $tenant = User::factory()->create();
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->create(['building_id' => $building->getKey()]);

        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);
        $building->users()->attach($tenant, ['role' => BuildingRole::Tenant->value]);
        $apartment->tenants()->attach($tenant);

        $ticket = Ticket::factory()->create([
            'apartment_id' => $apartment->getKey(),
            'assigned_to' => $manager->getKey(),
            'building_id' => $building->getKey(),
            'priority' => TicketPriority::High->value,
            'reported_by' => $tenant->getKey(),
            'status' => TicketStatus::New->value,
        ]);

        $dashboardService = $this->app->make(DashboardService::class);
        $ticketService = $this->app->make(TicketService::class);

        $initialDashboard = $dashboardService->getForUser($tenant, $building);

        $this->assertSame(1, $initialDashboard['total_tickets']);
        $this->assertSame(1, $initialDashboard['active_tickets']);
        $this->assertSame(0, $initialDashboard['resolved_tickets']);
        $this->assertCount(1, $initialDashboard['recent_tickets']);

        $ticketService->update($ticket->load('building'), $manager, [
            'status' => TicketStatus::Resolved,
            'status_note' => 'Issue resolved.',
        ]);

        $updatedDashboard = $dashboardService->getForUser($tenant, $building);

        $this->assertSame(1, $updatedDashboard['total_tickets']);
        $this->assertSame(0, $updatedDashboard['active_tickets']);
        $this->assertSame(1, $updatedDashboard['resolved_tickets']);
        $this->assertSame(TicketStatus::Resolved, $updatedDashboard['recent_tickets']->first()->status);
    }

    public function test_building_cache_is_invalidated_when_apartment_changes(): void
    {
        $building = Building::factory()->create([
            'address' => 'Palmoticeva 10',
            'name' => 'Komsije 10',
        ]);
        $apartment = Apartment::factory()->create([
            'building_id' => $building->getKey(),
            'floor' => '2',
            'number' => '21',
        ]);

        $buildingService = $this->app->make(BuildingService::class);
        $apartmentService = $this->app->make(ApartmentService::class);

        $cachedBuilding = $buildingService->getCachedData($building);

        $this->assertSame('Komsije 10', $cachedBuilding['name']);
        $this->assertCount(1, $cachedBuilding['apartments']);
        $this->assertSame('21', $cachedBuilding['apartments'][0]['number']);

        $apartmentService->update($apartment->load('building'), ['number' => '22']);

        $refreshedBuilding = $buildingService->getCachedData($building);

        $this->assertSame('22', $refreshedBuilding['apartments'][0]['number']);
    }

    public function test_announcement_cache_is_invalidated_when_draft_is_published(): void
    {
        $author = User::factory()->create();
        $building = Building::factory()->create();

        $announcementService = $this->app->make(AnnouncementService::class);

        $draft = $announcementService->create($building, $author, [
            'content' => 'Water shutdown on Friday.',
            'published_at' => null,
            'title' => 'Maintenance notice',
        ]);

        $cachedBeforePublish = $announcementService->getLatestForBuilding((int) $building->getKey());

        $this->assertCount(0, $cachedBeforePublish);

        $announcementService->update($draft, [
            'published_at' => now(),
        ]);

        $cachedAfterPublish = $announcementService->getLatestForBuilding((int) $building->getKey());

        $this->assertCount(1, $cachedAfterPublish);
        $this->assertSame('Maintenance notice', $cachedAfterPublish->first()->title);
        $this->assertNotNull($cachedAfterPublish->first()->published_at);
    }
}