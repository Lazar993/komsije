<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortalAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_in_through_the_web_portal(): void
    {
        $user = User::factory()->create([
            'email' => 'manager@upravnik.test',
        ]);

        $this->post('/login', [
            'email' => 'manager@upravnik.test',
            'password' => 'password',
        ])->assertRedirect(route('portal.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_switch_active_building_in_the_portal(): void
    {
        $user = User::factory()->create();
        $buildingA = Building::factory()->create(['name' => 'Alpha Residence']);
        $buildingB = Building::factory()->create(['name' => 'Beta Residence']);

        $buildingA->users()->attach($user, ['role' => BuildingRole::Tenant->value]);
        $buildingB->users()->attach($user, ['role' => BuildingRole::Tenant->value]);

        $this->actingAs($user)
            ->withSession(['current_building_id' => $buildingA->getKey()])
            ->post(route('portal.buildings.switch', $buildingB))
            ->assertRedirect(route('portal.dashboard'))
            ->assertSessionHas('current_building_id', $buildingB->getKey());
    }

    public function test_tenant_can_create_ticket_from_the_portal(): void
    {
        $tenant = User::factory()->create();
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->create(['building_id' => $building->getKey()]);

        $building->users()->attach($tenant, ['role' => BuildingRole::Tenant->value]);
        $apartment->tenants()->attach($tenant);

        $this->actingAs($tenant)
            ->withSession(['current_building_id' => $building->getKey()])
            ->post(route('portal.tickets.store'), [
                'building_id' => $building->getKey(),
                'apartment_id' => $apartment->getKey(),
                'title' => 'Water leak in hallway',
                'description' => 'There is a visible leak near the second-floor staircase.',
                'priority' => 'high',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'building_id' => $building->getKey(),
            'apartment_id' => $apartment->getKey(),
            'reported_by' => $tenant->getKey(),
            'title' => 'Water leak in hallway',
        ]);
    }

    public function test_tenant_can_quick_report_a_ticket_with_description_only(): void
    {
        $tenant = User::factory()->create();
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->create(['building_id' => $building->getKey()]);

        $building->users()->attach($tenant, ['role' => BuildingRole::Tenant->value]);
        $apartment->tenants()->attach($tenant);

        $description = 'The stairwell light on the third floor has been flickering since this morning.';

        $this->actingAs($tenant)
            ->withSession(['current_building_id' => $building->getKey()])
            ->post(route('portal.tickets.store'), [
                'building_id' => $building->getKey(),
                'apartment_id' => $apartment->getKey(),
                'description' => $description,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'building_id' => $building->getKey(),
            'apartment_id' => $apartment->getKey(),
            'reported_by' => $tenant->getKey(),
            'priority' => 'medium',
            'title' => $description,
        ]);
    }

    public function test_ticket_conversation_is_rendered_in_chronological_order_on_the_portal(): void
    {
        $tenant = User::factory()->create(['name' => 'Tenant User']);
        $manager = User::factory()->create(['name' => 'Manager User']);
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->create(['building_id' => $building->getKey()]);

        $building->users()->attach($tenant, ['role' => BuildingRole::Tenant->value]);
        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);
        $apartment->tenants()->attach($tenant);

        $ticket = Ticket::factory()->create([
            'building_id' => $building->getKey(),
            'apartment_id' => $apartment->getKey(),
            'reported_by' => $tenant->getKey(),
            'assigned_to' => $manager->getKey(),
            'title' => 'Elevator issue',
        ]);

        DB::table('ticket_comments')->insert([
            [
                'ticket_id' => $ticket->getKey(),
                'user_id' => $manager->getKey(),
                'body' => 'Manager reply comes first.',
                'created_at' => now()->subMinutes(10),
                'updated_at' => now()->subMinutes(10),
            ],
            [
                'ticket_id' => $ticket->getKey(),
                'user_id' => $tenant->getKey(),
                'body' => 'Tenant follow-up comes second.',
                'created_at' => now()->subMinutes(2),
                'updated_at' => now()->subMinutes(2),
            ],
        ]);

        $response = $this->actingAs($tenant)
            ->withSession(['current_building_id' => $building->getKey()])
            ->get(route('portal.tickets.show', $ticket));

        $response->assertOk();
        $response->assertSeeInOrder([
            'Manager reply comes first.',
            'Tenant follow-up comes second.',
        ], false);
    }

    public function test_ticket_conversation_can_be_polled_as_json_for_live_refresh(): void
    {
        $tenant = User::factory()->create(['name' => 'Tenant User']);
        $manager = User::factory()->create(['name' => 'Manager User']);
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->create(['building_id' => $building->getKey()]);

        $building->users()->attach($tenant, ['role' => BuildingRole::Tenant->value]);
        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);
        $apartment->tenants()->attach($tenant);

        $ticket = Ticket::factory()->create([
            'building_id' => $building->getKey(),
            'apartment_id' => $apartment->getKey(),
            'reported_by' => $tenant->getKey(),
            'assigned_to' => $manager->getKey(),
        ]);

        DB::table('ticket_comments')->insert([
            [
                'ticket_id' => $ticket->getKey(),
                'user_id' => $manager->getKey(),
                'body' => 'Please share a photo.',
                'created_at' => now()->subMinutes(5),
                'updated_at' => now()->subMinutes(5),
            ],
            [
                'ticket_id' => $ticket->getKey(),
                'user_id' => $tenant->getKey(),
                'body' => 'Uploading it now.',
                'created_at' => now()->subMinute(),
                'updated_at' => now()->subMinute(),
            ],
        ]);

        $response = $this->actingAs($tenant)
            ->withSession(['current_building_id' => $building->getKey()])
            ->getJson(route('portal.tickets.show', ['ticket' => $ticket, 'fragment' => 'conversation']));

        $response->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('countLabel', '2 poruka');

        $this->assertStringContainsString('Please share a photo.', $response->json('html'));
        $this->assertStringContainsString('Uploading it now.', $response->json('html'));
    }

    public function test_ticket_comment_submission_can_return_json_payload_for_async_chat_updates(): void
    {
        $tenant = User::factory()->create(['name' => 'Tenant User']);
        $manager = User::factory()->create(['name' => 'Manager User']);
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->create(['building_id' => $building->getKey()]);

        $building->users()->attach($tenant, ['role' => BuildingRole::Tenant->value]);
        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);
        $apartment->tenants()->attach($tenant);

        $ticket = Ticket::factory()->create([
            'building_id' => $building->getKey(),
            'apartment_id' => $apartment->getKey(),
            'reported_by' => $tenant->getKey(),
            'assigned_to' => $manager->getKey(),
        ]);

        $response = $this->actingAs($tenant)
            ->withSession(['current_building_id' => $building->getKey()])
            ->postJson(route('portal.tickets.comments.store', $ticket), [
                'building_id' => $building->getKey(),
                'body' => 'Fresh message from tenant.',
            ]);

        $response->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('countLabel', '1 poruka');

        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $ticket->getKey(),
            'user_id' => $tenant->getKey(),
            'body' => 'Fresh message from tenant.',
        ]);

        $this->assertStringContainsString('Fresh message from tenant.', $response->json('html'));
    }
}