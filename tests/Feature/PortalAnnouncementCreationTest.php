<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Models\Announcement;
use App\Models\Building;
use App\Models\User;
use App\Notifications\AnnouncementCreatedNotification;
use App\Notifications\AnnouncementPublishedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PortalAnnouncementCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_creation_without_publish_at_is_published_immediately(): void
    {
        Notification::fake();

        $manager = User::factory()->create();
        $tenant = User::factory()->create();
        $building = Building::factory()->create();

        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);
        $building->users()->attach($tenant, ['role' => BuildingRole::Tenant->value]);

        $this->actingAs($manager)
            ->withSession(['current_building_id' => $building->getKey()])
            ->post(route('portal.announcements.store'), [
                'building_id' => $building->getKey(),
                'title' => 'Garage cleaning reminder',
                'content' => 'Garage cleaning starts at 08:00 tomorrow.',
            ])
            ->assertRedirect();

        $announcement = Announcement::query()
            ->where('building_id', $building->getKey())
            ->where('author_id', $manager->getKey())
            ->where('title', 'Garage cleaning reminder')
            ->firstOrFail();

        $this->assertNotNull($announcement->published_at);
        Notification::assertNotSentTo($manager, AnnouncementCreatedNotification::class);
        Notification::assertNotSentTo($tenant, AnnouncementCreatedNotification::class);
        Notification::assertSentTo($tenant, AnnouncementPublishedNotification::class);
    }

    public function test_tenant_creation_stays_draft_and_notifies_manager_for_approval(): void
    {
        Notification::fake();

        $manager = User::factory()->create();
        $tenant = User::factory()->create();
        $building = Building::factory()->create();

        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);
        $building->users()->attach($tenant, ['role' => BuildingRole::Tenant->value]);

        $this->actingAs($tenant)
            ->withSession(['current_building_id' => $building->getKey()])
            ->post(route('portal.announcements.store'), [
                'building_id' => $building->getKey(),
                'title' => 'Hallway painting suggestion',
                'content' => 'Would it be possible to repaint hallway walls next month?',
                'published_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'is_important' => true,
            ])
            ->assertRedirect();

        $announcement = Announcement::query()
            ->where('building_id', $building->getKey())
            ->where('author_id', $tenant->getKey())
            ->where('title', 'Hallway painting suggestion')
            ->firstOrFail();

        $this->assertNull($announcement->published_at);
        $this->assertFalse((bool) $announcement->is_important);
        Notification::assertSentTo($manager, AnnouncementCreatedNotification::class);
        Notification::assertNotSentTo($tenant, AnnouncementCreatedNotification::class);
        Notification::assertNotSentTo($tenant, AnnouncementPublishedNotification::class);
    }
}
