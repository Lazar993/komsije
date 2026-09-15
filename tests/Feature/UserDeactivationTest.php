<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Models\Building;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeactivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivated_user_cannot_log_in_through_the_web_portal(): void
    {
        User::factory()->inactive()->create([
            'email' => 'tenant@upravnik.test',
        ]);

        $this->post('/login', [
            'email' => 'tenant@upravnik.test',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_active_user_can_still_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'active@upravnik.test',
        ]);

        $this->post('/login', [
            'email' => 'active@upravnik.test',
            'password' => 'password',
        ])->assertRedirect(route('portal.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_deactivated_user_with_active_session_is_logged_out(): void
    {
        $user = User::factory()->inactive()->create();
        $building = Building::factory()->create();
        $building->users()->attach($user, ['role' => BuildingRole::Tenant->value]);

        $this->actingAs($user)
            ->withSession(['current_building_id' => $building->getKey()])
            ->get(route('portal.dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_deactivated_user_cannot_log_in_through_the_api(): void
    {
        User::factory()->inactive()->create([
            'email' => 'api@upravnik.test',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'api@upravnik.test',
            'password' => 'password',
            'device_name' => 'phpunit',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_deactivated_user_cannot_access_the_admin_panel(): void
    {
        $manager = User::factory()->inactive()->create();
        $building = Building::factory()->create();
        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);
        $manager->syncBuildingRole($building->getKey());

        $panel = \Filament\Facades\Filament::getPanel('admin');

        $this->assertFalse($manager->canAccessPanel($panel));
    }
}
