<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Portal;

use App\Models\Announcement;
use App\Models\Building;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

abstract class PortalController
{
    use AuthorizesRequests;

    protected function portalView(Request $request, string $view, array $data = []): View
    {
        $accessibleBuildings = $data['accessibleBuildings'] ?? $this->accessibleBuildings($request->user());
        $currentBuilding = $data['currentBuilding'] ?? $this->resolveCurrentBuilding($request, $accessibleBuildings);
        $unreadAnnouncementsCount = 0;

        if ($currentBuilding !== null) {
            $unreadAnnouncementsCount = Announcement::query()
                ->where('building_id', $currentBuilding->getKey())
                ->whereNotNull('published_at')
                ->whereDoesntHave(
                    'reads',
                    fn (Builder $query): Builder => $query->where('user_id', $request->user()->getKey()),
                )
                ->count();
        }

        return view($view, array_merge($data, [
            'accessibleBuildings' => $accessibleBuildings,
            'currentBuilding' => $currentBuilding,
            'unreadAnnouncementsCount' => $unreadAnnouncementsCount,
        ]));
    }

    /**
     * @return Collection<int, Building>
     */
    protected function accessibleBuildings(User $user): Collection
    {
        return Building::query()
            ->when(! $user->isSuperAdmin(), fn ($query) => $query->whereHas('users', fn ($buildingQuery) => $buildingQuery->whereKey($user->getKey())))
            ->orderBy('name')
            ->get();
    }

    /**
     * @param Collection<int, Building>|null $accessibleBuildings
     */
    protected function resolveCurrentBuilding(Request $request, ?Collection $accessibleBuildings = null): ?Building
    {
        $accessibleBuildings ??= $this->accessibleBuildings($request->user());
        $currentBuildingId = $request->session()->get('current_building_id');

        $currentBuilding = $currentBuildingId !== null
            ? $accessibleBuildings->firstWhere('id', (int) $currentBuildingId)
            : null;

        if ($currentBuilding === null) {
            $currentBuilding = $accessibleBuildings->first();
        }

        if ($currentBuilding !== null) {
            $request->session()->put('current_building_id', $currentBuilding->getKey());
        } else {
            $request->session()->forget('current_building_id');
        }

        return $currentBuilding;
    }
}