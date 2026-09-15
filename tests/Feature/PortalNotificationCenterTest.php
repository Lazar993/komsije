<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Models\Announcement;
use App\Models\Building;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PortalNotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_recent_building_scoped_notifications(): void
    {
        [$tenant, $building] = $this->createTenantAndBuilding();
        $announcement = Announcement::factory()->create([
            'building_id' => $building->getKey(),
            'author_id' => $tenant->getKey(),
            'published_at' => now(),
        ]);

        $this->createNotification($tenant, [
            'type' => 'announcement_published',
            'building_id' => $building->getKey(),
            'announcement_id' => $announcement->getKey(),
            'title' => $announcement->title,
            'message' => 'A new building announcement is available.',
        ]);

        $response = $this->actingAs($tenant)
            ->withSession(['current_building_id' => $building->getKey()])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('portal.notifications.index'));

        $response->assertOk()
            ->assertJson(['has_more' => false, 'next_page' => null]);

        $this->assertStringContainsString($announcement->title, $response->json('html'));
        $this->assertStringContainsString(route('portal.announcements.show', $announcement), $response->json('html'));
    }

    public function test_it_excludes_notifications_older_than_the_configured_window(): void
    {
        config()->set('notifications.recent_days', 7);

        [$tenant, $building] = $this->createTenantAndBuilding();

        $this->createNotification($tenant, [
            'type' => 'ticket_commented',
            'building_id' => $building->getKey(),
            'ticket_id' => 1,
            'title' => 'Old notification',
            'message' => 'Should be hidden',
        ], createdAt: now()->subDays(10));

        $response = $this->actingAs($tenant)
            ->withSession(['current_building_id' => $building->getKey()])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('portal.notifications.index'));

        $response->assertOk();
        $this->assertSame('', trim($response->json('html')));
    }

    public function test_it_excludes_notifications_from_other_buildings(): void
    {
        [$tenant, $building] = $this->createTenantAndBuilding();
        $otherBuilding = Building::factory()->create();

        $this->createNotification($tenant, [
            'type' => 'ticket_commented',
            'building_id' => $otherBuilding->getKey(),
            'ticket_id' => 1,
            'title' => 'Other building',
            'message' => 'Should be hidden',
        ]);

        $response = $this->actingAs($tenant)
            ->withSession(['current_building_id' => $building->getKey()])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('portal.notifications.index'));

        $response->assertOk();
        $this->assertSame('', trim($response->json('html')));
    }

    public function test_it_paginates_with_a_show_more_signal(): void
    {
        config()->set('notifications.per_page', 8);

        [$tenant, $building] = $this->createTenantAndBuilding();

        foreach (range(1, 9) as $index) {
            $this->createNotification($tenant, [
                'type' => 'ticket_commented',
                'building_id' => $building->getKey(),
                'ticket_id' => $index,
                'title' => "Notification {$index}",
                'message' => 'Message',
            ]);
        }

        $response = $this->actingAs($tenant)
            ->withSession(['current_building_id' => $building->getKey()])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('portal.notifications.index'));

        $response->assertOk()->assertJson(['has_more' => true, 'next_page' => 2]);

        $secondPage = $this->actingAs($tenant)
            ->withSession(['current_building_id' => $building->getKey()])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('portal.notifications.index', ['page' => 2]));

        $secondPage->assertOk()->assertJson(['has_more' => false, 'next_page' => null]);
    }

    /**
     * @return array{0: User, 1: Building}
     */
    private function createTenantAndBuilding(): array
    {
        $tenant = User::factory()->create();
        $building = Building::factory()->create();

        $building->users()->attach($tenant, ['role' => BuildingRole::Tenant->value]);

        return [$tenant, $building];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createNotification(User $user, array $data, ?\Illuminate\Support\Carbon $createdAt = null): void
    {
        $createdAt ??= now();

        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\'.($data['type'] ?? 'Notification'),
            'data' => $data,
            'read_at' => null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
