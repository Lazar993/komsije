<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Invite;
use App\Models\User;
use App\Notifications\TenantInviteNotification;
use App\Services\InviteService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InviteRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_invite_page_is_rendered(): void
    {
        [$building, $apartment] = $this->makeBuildingContext();

        $invite = Invite::query()->create([
            'apartment_id' => $apartment->getKey(),
            'building_id' => $building->getKey(),
            'created_by' => User::factory()->create()->getKey(),
            'email' => 'tenant@example.com',
            'role' => BuildingRole::Tenant->value,
        ]);

        $response = $this->get(route('invite.show', $invite->token));

        $response->assertOk();
        $response->assertSee($building->name);
        $response->assertSee($apartment->number);
        $response->assertSee('tenant@example.com');
    }

    public function test_invite_creation_sends_email_notification(): void
    {
        Notification::fake();

        [$building, $apartment] = $this->makeBuildingContext();
        $manager = User::factory()->create();

        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);

        $invite = app(InviteService::class)->create($building, $apartment, $manager, 'tenant@example.com');

        Notification::assertSentOnDemand(TenantInviteNotification::class, function (TenantInviteNotification $notification, array $channels, object $notifiable) use ($invite): bool {
            return in_array('mail', $channels, true)
                && ($notifiable->routes['mail'] ?? null) === 'tenant@example.com';
        });

        $this->assertNotEmpty($invite->token);
    }

    public function test_invite_acceptance_creates_tenant_memberships_and_logs_user_in(): void
    {
        [$building, $apartment] = $this->makeBuildingContext();
        $manager = User::factory()->create();

        $invite = Invite::query()->create([
            'apartment_id' => $apartment->getKey(),
            'building_id' => $building->getKey(),
            'created_by' => $manager->getKey(),
            'email' => 'tenant@example.com',
            'role' => BuildingRole::Tenant->value,
        ]);

        $response = $this->post(route('invite.store', $invite->token), [
            'email' => 'tenant@example.com',
            'name' => 'New Tenant',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('portal.dashboard'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'tenant@example.com')->firstOrFail();

        $this->assertTrue($building->users()->whereKey($user->getKey())->exists());
        $this->assertTrue($apartment->tenants()->whereKey($user->getKey())->exists());
        $this->assertTrue($user->hasRoleInBuilding('tenant', $building->getKey()));
        $this->assertNotNull($invite->fresh()?->used_at);
        $this->assertSame($building->getKey(), session('current_building_id'));
    }

    public function test_admin_invite_acceptance_creates_manager_membership_and_logs_user_in(): void
    {
        [$building] = $this->makeBuildingContext();
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $invite = Invite::query()->create([
            'apartment_id' => null,
            'building_id' => $building->getKey(),
            'created_by' => $superAdmin->getKey(),
            'email' => 'manager@example.com',
            'role' => BuildingRole::PropertyManager->value,
        ]);

        $response = $this->post(route('invite.store', $invite->token), [
            'email' => 'manager@example.com',
            'name' => 'Future Manager',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('portal.dashboard'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'manager@example.com')->firstOrFail();

        $this->assertTrue($building->managers()->whereKey($user->getKey())->exists());
        $this->assertTrue($user->hasRoleInBuilding('admin', $building->getKey()));
        $this->assertFalse($user->apartments()->exists());
        $this->assertNotNull($invite->fresh()?->used_at);
        $this->assertSame($building->getKey(), session('current_building_id'));
    }

    public function test_building_admin_cannot_create_property_manager_invite(): void
    {
        [$building, $apartment] = $this->makeBuildingContext();
        $manager = User::factory()->create();

        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);

        $this->expectException(AuthorizationException::class);

        app(InviteService::class)->create(
            $building,
            $apartment,
            $manager,
            'manager@example.com',
            BuildingRole::PropertyManager,
        );
    }

    public function test_used_or_email_mismatched_invites_cannot_be_reused(): void
    {
        [$building, $apartment] = $this->makeBuildingContext();

        $invite = Invite::query()->create([
            'apartment_id' => $apartment->getKey(),
            'building_id' => $building->getKey(),
            'created_by' => User::factory()->create()->getKey(),
            'email' => 'tenant@example.com',
            'role' => BuildingRole::Tenant->value,
            'used_at' => now(),
        ]);

        $this->get(route('invite.show', $invite->token))
            ->assertStatus(410)
            ->assertSee('Ovaj poziv više nije dostupan');

        $freshInvite = Invite::query()->create([
            'apartment_id' => $apartment->getKey(),
            'building_id' => $building->getKey(),
            'created_by' => User::factory()->create()->getKey(),
            'email' => 'tenant@example.com',
            'role' => BuildingRole::Tenant->value,
        ]);

        $response = $this->from(route('invite.show', $freshInvite->token))->post(route('invite.store', $freshInvite->token), [
            'email' => 'other@example.com',
            'name' => 'Wrong Email',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('invite.show', $freshInvite->token));
        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'other@example.com']);
    }

    public function test_unknown_invite_token_shows_friendly_invalid_page(): void
    {
        $this->get(route('invite.show', 'missing-token'))
            ->assertStatus(404)
            ->assertSee('Ovaj poziv više nije dostupan');
    }

    /**
     * @return array{0: Building, 1: Apartment}
     */
    private function makeBuildingContext(): array
    {
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->create([
            'building_id' => $building->getKey(),
            'number' => '12',
        ]);

        return [$building, $apartment];
    }
}