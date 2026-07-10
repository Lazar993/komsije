<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Requests\Auth\StoreBuildingJoinRequest;
use App\Services\BuildingJoinRequestService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

final class JoinBuildingController
{
    public function __construct(private readonly BuildingJoinRequestService $joinRequests)
    {
    }

    public function show(string $token): View|Response
    {
        $building = $this->joinRequests->findBuildingByToken($token);

        if ($building === null) {
            return response()->view('join.invalid', [], 404);
        }

        return view('join.show', [
            'building' => $building,
            'token' => $token,
        ]);
    }

    public function store(StoreBuildingJoinRequest $request, string $token): RedirectResponse
    {
        $building = $this->joinRequests->findBuildingByToken($token);

        if ($building === null) {
            return redirect()->route('join.show', ['token' => $token]);
        }

        $this->joinRequests->create($building, $request->validated(), $request);

        return redirect()->route('join.show', ['token' => $token])->with('status', __('Hvala. Vaš zahtev je poslat upravniku na odobrenje.'));
    }
}
