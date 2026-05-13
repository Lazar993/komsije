<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationLaunchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_notification_launch_redirects_to_login_and_preserves_the_final_target(): void
    {
        $target = '/portal/announcements/15';

        $this->get(route('notification.launch', ['target' => $target], false))
            ->assertRedirect(route('login'))
            ->assertSessionHas('url.intended', $target);
    }

    public function test_authenticated_notification_launch_redirects_directly_to_the_target(): void
    {
        $user = User::factory()->create();
        $target = '/portal/tickets/9';

        $this->actingAs($user)
            ->get(route('notification.launch', ['target' => $target], false))
            ->assertRedirect($target);
    }

    public function test_invalid_notification_target_falls_back_to_the_dashboard_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('notification.launch', ['target' => 'https://example.com'], false))
            ->assertRedirect(route('portal.dashboard'));
    }
}