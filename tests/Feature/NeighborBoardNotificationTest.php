<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Enums\NeighborBoardCategory;
use App\Models\Building;
use App\Models\User;
use App\Notifications\NeighborBoardPostCreatedNotification;
use App\Services\NeighborBoardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NeighborBoardNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_push_notification_is_sent_to_building_residents_when_checkbox_is_checked(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $recipient = User::factory()->create();
        $building = Building::factory()->create();

        $building->users()->attach($author, ['role' => BuildingRole::Tenant->value]);
        $building->users()->attach($recipient, ['role' => BuildingRole::Tenant->value]);

        $this->app->make(NeighborBoardService::class)->create($building, $author, [
            'category' => NeighborBoardCategory::Question,
            'title' => 'Test objava',
            'description' => 'Opis objave',
            'images' => [],
            'notify_residents' => true,
        ]);

        Notification::assertSentTo($recipient, NeighborBoardPostCreatedNotification::class);
        Notification::assertNotSentTo($author, NeighborBoardPostCreatedNotification::class);

        Notification::assertSentTo(
            $recipient,
            NeighborBoardPostCreatedNotification::class,
            function (NeighborBoardPostCreatedNotification $notification, array $channels) use ($recipient): bool {
                $resolvedChannels = $notification->via($recipient);

                return ! in_array('mail', $resolvedChannels, true);
            },
        );
    }

    public function test_no_push_is_sent_when_checkbox_is_not_checked(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $recipient = User::factory()->create();
        $building = Building::factory()->create();

        $building->users()->attach($author, ['role' => BuildingRole::Tenant->value]);
        $building->users()->attach($recipient, ['role' => BuildingRole::Tenant->value]);

        $this->app->make(NeighborBoardService::class)->create($building, $author, [
            'category' => NeighborBoardCategory::Question,
            'title' => 'Bez push-a',
            'description' => 'Opis objave',
            'images' => [],
            'notify_residents' => false,
        ]);

        Notification::assertNotSentTo($recipient, NeighborBoardPostCreatedNotification::class);
    }
}
