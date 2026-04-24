<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Building;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePortalBuildingContext
{
    public function __construct(private readonly TenantContext $tenantContext)
    {
    }

    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $user = $request->user();

        abort_if($user === null, Response::HTTP_UNAUTHORIZED);

        $buildingQuery = Building::query()->orderBy('name');

        if (! $user->isSuperAdmin()) {
            $buildingQuery->whereHas('users', fn ($query) => $query->whereKey($user->getKey()));
        }

        $currentBuildingId = $request->session()->get('current_building_id');
        $building = $currentBuildingId !== null ? (clone $buildingQuery)->find($currentBuildingId) : null;

        if ($building === null) {
            $building = (clone $buildingQuery)->first();
        }

        if ($building === null) {
            return redirect()->route('portal.dashboard')->with('status', __('Assign a user to a building before using the resident portal.'));
        }

        $request->session()->put('current_building_id', $building->getKey());
        setPermissionsTeamId($building->getKey());
        $this->tenantContext->setBuilding($building);
        $request->attributes->set('currentBuilding', $building);

        return $next($request);
    }
}