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
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnnouncementApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_created_announcement_is_published_immediately_when_publish_time_is_not_provided(): void
    {
        Notification::fake();

        $manager = User::factory()->create();
        $tenant = User::factory()->create();
        $building = Building::factory()->create();

        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);
        $building->users()->attach($tenant, ['role' => BuildingRole::Tenant->value]);

        Sanctum::actingAs($manager);

        $response = $this->postJson('/api/announcements', [
            'building_id' => $building->getKey(),
            'title' => 'Lift maintenance notice',
            'content' => 'Lift maintenance starts at 11:00 and should finish by noon.',
            'link_url' => 'https://example.com/lift-maintenance',
        ]);

        $response->assertCreated();

        $announcement = Announcement::query()
            ->where('building_id', $building->getKey())
            ->where('author_id', $manager->getKey())
            ->where('title', 'Lift maintenance notice')
            ->firstOrFail();

        $this->assertNotNull($announcement->published_at);
        Notification::assertNotSentTo($manager, AnnouncementCreatedNotification::class);
        Notification::assertNotSentTo($tenant, AnnouncementCreatedNotification::class);
        Notification::assertSentTo($tenant, AnnouncementPublishedNotification::class);
    }

    public function test_tenant_can_create_announcement_and_manager_is_notified_for_approval(): void
    {
        Notification::fake();

        $manager = User::factory()->create();
        $tenant = User::factory()->create();
        $building = Building::factory()->create();

        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);
        $building->users()->attach($tenant, ['role' => BuildingRole::Tenant->value]);

        Sanctum::actingAs($tenant);

        $response = $this->postJson('/api/announcements', [
            'building_id' => $building->getKey(),
            'title' => 'Water shutdown notice',
            'content' => 'Water will be unavailable between 10:00 and 12:00.',
            'link_url' => 'https://example.com/water-maintenance',
            'published_at' => now()->toIso8601String(),
            'is_important' => true,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('announcements', [
            'building_id' => $building->getKey(),
            'author_id' => $tenant->getKey(),
            'title' => 'Water shutdown notice',
            'link_url' => 'https://example.com/water-maintenance',
            'is_important' => false,
            'published_at' => null,
        ]);

        Notification::assertSentTo($manager, AnnouncementCreatedNotification::class);
        Notification::assertNotSentTo($tenant, AnnouncementCreatedNotification::class);
    }

    public function test_announcement_can_store_multiple_links(): void
    {
        $manager = User::factory()->create();
        $building = Building::factory()->create();

        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);

        Sanctum::actingAs($manager);

        $response = $this->postJson('/api/announcements', [
            'building_id' => $building->getKey(),
            'title' => 'Links update',
            'content' => 'Check all resources below.',
            'links' => [
                'https://example.com/one',
                'https://example.com/two',
                'https://example.com/three',
            ],
        ]);

        $response->assertCreated();

        $announcement = Announcement::query()->latest('id')->firstOrFail();

        $this->assertSame('https://example.com/one', $announcement->link_url);
        $this->assertSame([
            'https://example.com/one',
            'https://example.com/two',
            'https://example.com/three',
        ], $announcement->links);
    }
}