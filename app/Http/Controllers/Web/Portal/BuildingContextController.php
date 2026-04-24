<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Portal;

use App\Models\Building;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class BuildingContextController extends PortalController
{
    public function __invoke(Request $request, Building $building): RedirectResponse
    {
        $this->authorize('view', $building);

        $request->session()->put('current_building_id', $building->getKey());

        return redirect()->route('portal.dashboard')->with('status', __('Active building updated.'));
    }
}