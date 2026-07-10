<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Poll;
use App\Models\User;
use App\Rules\BuildingAcceptsWrites;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AdminReadOnlyForSuspendedBuildingTest extends TestCase
{
    use RefreshDatabase;

    private function manager(): User
    {
        return User::factory()->create();
    }

    public function test_writable_select_options_exclude_read_only_buildings_for_managers(): void
    {
        $manager = $this->manager();
        $active = Building::factory()->active()->create(['name' => 'Active House']);
        $suspended = Building::factory()->suspended()->create(['name' => 'Suspended House']);
        $archived = Building::factory()->archived()->create(['name' => 'Archived House']);

        foreach ([$active, $suspended, $archived] as $building) {
            $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);
        }

        $options = Building::writableSelectOptions($manager);

        $this->assertArrayHasKey($active->getKey(), $options);
        $this->assertArrayNotHasKey($suspended->getKey(), $options);
        $this->assertArrayNotHasKey($archived->getKey(), $options);
    }

    public function test_super_admin_sees_every_building_in_writable_options(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        Building::factory()->active()->create();
        Building::factory()->suspended()->create();

        $this->assertCount(2, Building::writableSelectOptions($superAdmin));
    }

    public function test_manager_cannot_create_or_edit_content_in_suspended_building(): void
    {
        $manager = $this->manager();
        $suspended = Building::factory()->suspended()->create();
        $suspended->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);
        $apartment = Apartment::factory()->create(['building_id' => $suspended->getKey()]);
        $poll = Poll::query()->create([
            'building_id' => $suspended->getKey(),
            'title' => 'Test poll',
            'is_active' => true,
        ]);

        $this->assertFalse($manager->can('create', [Apartment::class, $suspended]));
        $this->assertFalse($manager->can('update', $apartment));
        $this->assertFalse($manager->can('delete', $apartment));
        $this->assertFalse($manager->can('update', $poll));
    }

    public function test_manager_can_still_create_content_in_active_building(): void
    {
        $manager = $this->manager();
        $active = Building::factory()->active()->create();
        $active->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);

        $this->assertTrue($manager->can('create', [Apartment::class, $active]));
    }

    public function test_building_accepts_writes_rule_blocks_read_only_buildings(): void
    {
        $manager = $this->manager();
        $active = Building::factory()->active()->create();
        $suspended = Building::factory()->suspended()->create();
        Auth::login($manager);

        $this->assertTrue($this->passesRule($active->getKey()));
        $this->assertFalse($this->passesRule($suspended->getKey()));

        // Super admins bypass the rule.
        Auth::login(User::factory()->create(['is_super_admin' => true]));
        $this->assertTrue($this->passesRule($suspended->getKey()));
    }

    private function passesRule(int $buildingId): bool
    {
        $passed = true;
        (new BuildingAcceptsWrites())->validate('building_id', $buildingId, function () use (&$passed): void {
            $passed = false;
        });

        return $passed;
    }
}
