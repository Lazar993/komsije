<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\Buildings\Pages\ViewBuilding;
use App\Models\Building;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViewBuildingPageSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_render_building_view_with_analytics(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $building = Building::factory()->trial()->create();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($superAdmin);

        Livewire::test(ViewBuilding::class, ['record' => $building->getRouteKey()])
            ->assertOk()
            ->assertSee(__('Health score'))
            ->assertSee(__('Subscription lifecycle'));
    }
}
