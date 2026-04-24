<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Models\Building;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_uses_serbian_by_default(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Prijavite se na portal')
            ->assertSee('Jezik');
    }

    public function test_guest_can_switch_login_page_to_english(): void
    {
        $this->from(route('login'))
            ->post(route('locale.update'), ['locale' => 'en'])
            ->assertRedirect(route('login'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sign in to the portal')
            ->assertSee('Language');
    }

    public function test_authenticated_user_locale_choice_is_persisted(): void
    {
        $user = User::factory()->create(['locale' => 'sr']);
        $building = Building::factory()->create();

        $building->users()->attach($user, ['role' => BuildingRole::Tenant->value]);

        $this->actingAs($user)
            ->from(route('portal.dashboard'))
            ->withSession(['current_building_id' => $building->getKey()])
            ->post(route('locale.update'), ['locale' => 'en'])
            ->assertRedirect(route('portal.dashboard'));

        $this->assertDatabaseHas('users', [
            'id' => $user->getKey(),
            'locale' => 'en',
        ]);

        $this->withSession(['current_building_id' => $building->getKey()])
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSee('Home')
            ->assertDontSee('Početna')
            ->assertSee('Language');
    }
}