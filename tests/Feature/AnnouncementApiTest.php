<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Models\Building;
use App\Models\User;
use App\Notifications\AnnouncementCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnnouncementApiTest extends TestCase
{
    use RefreshDatabase;

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
            'published_at' => now()->toIso8601String(),
            'is_important' => true,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('announcements', [
            'building_id' => $building->getKey(),
            'author_id' => $tenant->getKey(),
            'title' => 'Water shutdown notice',
            'is_important' => false,
            'published_at' => null,
        ]);

        Notification::assertSentTo($manager, AnnouncementCreatedNotification::class);
        Notification::assertNotSentTo($tenant, AnnouncementCreatedNotification::class);
    }
}