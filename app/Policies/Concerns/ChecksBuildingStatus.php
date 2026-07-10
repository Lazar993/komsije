<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Models\Building;

/**
 * Shared guard that makes write abilities respect the building lifecycle.
 *
 * Suspended and archived buildings are read-only for managers and tenants;
 * super admins bypass all policies via Gate::before, so they can still manage
 * subscriptions. Reading history is never blocked here — only mutating
 * abilities should consult this guard.
 */
trait ChecksBuildingStatus
{
    protected function buildingAllowsWrites(?Building $building): bool
    {
        // When the building context is unknown (e.g. a generic create ability
        // check without a specific building) we defer to the concrete,
        // building-scoped check performed at persistence time.
        if ($building === null) {
            return true;
        }

        return $building->allowsWrites();
    }
}
