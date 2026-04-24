<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BuildingRole;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Announcement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::factory()->superAdmin()->create([
            'name' => 'Super Admin',
            'email' => 'admin@upravnik.test',
        ]);
        $superAdmin->syncGlobalRoles(['super_admin']);

        $manager = User::factory()->create([
            'name' => 'Mila Manager',
            'email' => 'manager@upravnik.test',
        ]);

        $secondaryManager = User::factory()->create([
            'name' => 'Nikola Manager',
            'email' => 'manager2@upravnik.test',
        ]);

        $tenant = User::factory()->create([
            'name' => 'Tara Tenant',
            'email' => 'tenant@upravnik.test',
        ]);

        $otherTenants = User::factory()->count(3)->create();

        $buildingA = Building::factory()->create([
            'name' => 'Sunrise Towers',
            'address' => '12 Palm Street',
            'created_by' => $superAdmin->getKey(),
            'billing_customer_reference' => 'cus_demo_sunrise',
        ]);

        $buildingB = Building::factory()->create([
            'name' => 'River Park Residences',
            'address' => '88 Riverside Avenue',
            'created_by' => $superAdmin->getKey(),
            'billing_customer_reference' => 'cus_demo_riverpark',
        ]);

        $buildingA->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);
        $buildingB->users()->attach($secondaryManager, ['role' => BuildingRole::PropertyManager->value]);
        $manager->syncBuildingRole($buildingA->getKey(), BuildingRole::PropertyManager->permissionRoleName());
        $secondaryManager->syncBuildingRole($buildingB->getKey(), BuildingRole::PropertyManager->permissionRoleName());

        $apartmentsA = Apartment::factory()->count(4)->create(['building_id' => $buildingA->getKey()]);
        $apartmentsB = Apartment::factory()->count(3)->create(['building_id' => $buildingB->getKey()]);

        $primaryApartment = $apartmentsA->first();
        $primaryApartment->tenants()->attach($tenant);
        $buildingA->users()->attach($tenant, ['role' => BuildingRole::Tenant->value]);
        $tenant->syncBuildingRole($buildingA->getKey(), BuildingRole::Tenant->permissionRoleName());

        foreach ($otherTenants as $index => $resident) {
            $apartment = $index < 2 ? $apartmentsA[$index + 1] : $apartmentsB[$index - 2];
            $apartment->tenants()->attach($resident);
            $apartment->building->users()->attach($resident, ['role' => BuildingRole::Tenant->value]);
            $resident->syncBuildingRole($apartment->building_id, BuildingRole::Tenant->permissionRoleName());
        }

        $ticket = Ticket::factory()->create([
            'building_id' => $buildingA->getKey(),
            'apartment_id' => $primaryApartment->getKey(),
            'reported_by' => $tenant->getKey(),
            'assigned_to' => $manager->getKey(),
            'title' => 'Water leak in kitchen',
            'description' => 'There is a leak under the kitchen sink since this morning.',
            'status' => TicketStatus::InProgress,
            'priority' => TicketPriority::High,
        ]);

        $ticket->comments()->create([
            'user_id' => $manager->getKey(),
            'body' => 'Maintenance visit scheduled for tomorrow at 09:00.',
        ]);

        $ticket->statusHistory()->createMany([
            [
                'from_status' => null,
                'to_status' => TicketStatus::New,
                'changed_by' => $tenant->getKey(),
                'note' => 'Tenant created the ticket.',
            ],
            [
                'from_status' => TicketStatus::New,
                'to_status' => TicketStatus::InProgress,
                'changed_by' => $manager->getKey(),
                'note' => 'Manager acknowledged the issue.',
            ],
        ]);

        $announcement = Announcement::factory()->create([
            'building_id' => $buildingA->getKey(),
            'author_id' => $manager->getKey(),
            'title' => 'Water shutdown on Friday',
            'content' => 'Water will be unavailable from 10:00 to 13:00 due to pipe maintenance.',
            'published_at' => now()->subDay(),
        ]);

        $announcement->reads()->create([
            'user_id' => $tenant->getKey(),
            'read_at' => now()->subHours(6),
        ]);
    }
}