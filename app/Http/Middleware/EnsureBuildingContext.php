<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Building;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

final class EnsureBuildingContext
{
    public function __construct(private readonly TenantContext $tenantContext)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        $buildingId = $this->resolveBuildingId($request);

        if ($buildingId === null) {
            throw ValidationException::withMessages([
                'building_id' => ['The building context is required. Supply it as the building_id parameter or X-Building-Id header.'],
            ]);
        }

        $building = Building::query()->findOrFail($buildingId);

        abort_unless($user->belongsToBuilding($building->getKey()), Response::HTTP_FORBIDDEN, 'You do not belong to the selected building.');

        setPermissionsTeamId($building->getKey());
        $this->tenantContext->setBuilding($building);
        $request->attributes->set('currentBuilding', $building);
        $request->attributes->set('currentBuildingId', $building->getKey());

        return $next($request);
    }

    private function resolveBuildingId(Request $request): ?int
    {
        $routeBuilding = $request->route('building');

        if ($routeBuilding instanceof Building) {
            return (int) $routeBuilding->getKey();
        }

        $rawValue = $request->header('X-Building-Id')
            ?? $request->route('building')
            ?? $request->input('building_id')
            ?? $request->query('building_id');

        if ($rawValue === null || $rawValue === '') {
            return null;
        }

        return (int) $rawValue;
    }
}