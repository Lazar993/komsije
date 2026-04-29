<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeviceTokenRequest;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

final class DeviceTokenController extends Controller
{
    public function store(StoreDeviceTokenRequest $request): JsonResponse
    {
        $user = $request->user();
        $token = (string) $request->validated('token');

        $userAgent = substr((string) $request->userAgent(), 0, 512) ?: null;
        $deviceType = $request->validated('device_type');

        // Reassign token to current user if it was previously bound to another account
        // (e.g. shared device after re-login). This also handles the duplicate case.
        $record = DeviceToken::query()->updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $user->getKey(),
                'device_type' => $deviceType,
                'user_agent' => $userAgent,
                'last_used_at' => Carbon::now(),
            ],
        );

        return response()->json([
            'id' => $record->getKey(),
            'created' => $record->wasRecentlyCreated,
        ], $record->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(StoreDeviceTokenRequest $request): JsonResponse
    {
        $user = $request->user();
        $token = (string) $request->validated('token');

        DeviceToken::query()
            ->where('user_id', $user->getKey())
            ->where('token', $token)
            ->delete();

        return response()->json(['deleted' => true]);
    }
}
