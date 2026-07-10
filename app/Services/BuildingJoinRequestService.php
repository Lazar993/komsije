<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BuildingJoinRequestStatus;
use App\Enums\BuildingRole;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\BuildingJoinRequest;
use App\Models\Invite;
use App\Models\User;
use App\Notifications\BuildingJoinRequestRejectedNotification;
use App\Notifications\NewResidentJoinRequestNotification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class BuildingJoinRequestService
{
    public function __construct(
        private readonly BuildingOnboardingService $onboarding,
        private readonly InviteService $invites,
    ) {
    }

    public function findBuildingByToken(string $token): ?Building
    {
        return $this->onboarding->findByToken($token);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(Building $building, array $data, Request $request): BuildingJoinRequest
    {
        $normalizedEmail = Str::lower(trim((string) $data['email']));

        $this->guardAgainstObviousSpam($data);
        $this->guardAgainstDuplicates($building, $normalizedEmail);

        $joinRequest = BuildingJoinRequest::query()->create([
            'building_id' => $building->getKey(),
            'first_name' => trim((string) $data['first_name']),
            'last_name' => trim((string) $data['last_name']),
            'email' => $normalizedEmail,
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'apartment_number' => trim((string) $data['apartment_number']),
            'status' => BuildingJoinRequestStatus::Pending,
            'request_ip' => (string) $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
        ])->load('building');

        $managers = $building->managers()->get();

        if ($managers->isNotEmpty()) {
            Notification::send($managers, new NewResidentJoinRequestNotification($joinRequest));
        }

        return $joinRequest;
    }

    public function approve(BuildingJoinRequest $joinRequest, User $actor): Invite
    {
        return DB::transaction(function () use ($joinRequest, $actor): Invite {
            $locked = BuildingJoinRequest::query()
                ->with('building')
                ->lockForUpdate()
                ->find($joinRequest->getKey());

            if ($locked === null) {
                throw (new ModelNotFoundException())->setModel(BuildingJoinRequest::class, [$joinRequest->getKey()]);
            }

            if ($locked->status !== BuildingJoinRequestStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => [__('This resident request is already processed.')],
                ]);
            }

            $building = $locked->building;

            if ($building === null) {
                throw ValidationException::withMessages([
                    'building' => [__('Building not found for this request.')],
                ]);
            }

            $apartment = Apartment::query()
                ->where('building_id', $building->getKey())
                ->whereRaw('LOWER(number) = ?', [Str::lower($locked->apartment_number)])
                ->first();

            if ($apartment === null) {
                $apartment = Apartment::query()->create([
                    'building_id' => $building->getKey(),
                    'number' => $locked->apartment_number,
                    'floor' => null,
                ]);
            }

            $invite = $this->invites->create(
                $building,
                $apartment,
                $actor,
                $locked->email,
                BuildingRole::Tenant,
            );

            $locked->forceFill([
                'status' => BuildingJoinRequestStatus::Approved,
                'approved_by' => $actor->getKey(),
                'approved_at' => now(),
                'rejected_at' => null,
                'rejection_reason' => null,
            ])->save();

            return $invite;
        });
    }

    public function reject(BuildingJoinRequest $joinRequest, User $actor, ?string $reason = null): BuildingJoinRequest
    {
        return DB::transaction(function () use ($joinRequest, $actor, $reason): BuildingJoinRequest {
            $locked = BuildingJoinRequest::query()
                ->with('building')
                ->lockForUpdate()
                ->find($joinRequest->getKey());

            if ($locked === null) {
                throw (new ModelNotFoundException())->setModel(BuildingJoinRequest::class, [$joinRequest->getKey()]);
            }

            if ($locked->status !== BuildingJoinRequestStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => [__('This resident request is already processed.')],
                ]);
            }

            $locked->forceFill([
                'status' => BuildingJoinRequestStatus::Rejected,
                'approved_by' => $actor->getKey(),
                'rejected_at' => now(),
                'rejection_reason' => filled($reason) ? trim((string) $reason) : null,
            ])->save();

            Notification::route('mail', $locked->email)
                ->notify(new BuildingJoinRequestRejectedNotification($locked));

            return $locked;
        });
    }

    private function guardAgainstDuplicates(Building $building, string $normalizedEmail): void
    {
        $alreadyResident = User::query()
            ->where('email', $normalizedEmail)
            ->whereHas('buildings', fn ($query) => $query->where('buildings.id', $building->getKey()))
            ->exists();

        if ($alreadyResident) {
            throw ValidationException::withMessages([
                'email' => [__('This email already belongs to a resident in this building.')],
            ]);
        }

        $activeRequestExists = BuildingJoinRequest::query()
            ->where('building_id', $building->getKey())
            ->where('email', $normalizedEmail)
            ->whereIn('status', [
                BuildingJoinRequestStatus::Pending->value,
                BuildingJoinRequestStatus::Approved->value,
            ])
            ->exists();

        if ($activeRequestExists) {
            throw ValidationException::withMessages([
                'email' => [__('A resident request for this email already exists for this building.')],
            ]);
        }

        $activeInviteExists = Invite::query()
            ->valid()
            ->where('building_id', $building->getKey())
            ->where('email', $normalizedEmail)
            ->where('role', BuildingRole::Tenant->value)
            ->exists();

        if ($activeInviteExists) {
            throw ValidationException::withMessages([
                'email' => [__('An active invitation already exists for this email. Please check your inbox.')],
            ]);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function guardAgainstObviousSpam(array $data): void
    {
        $haystack = mb_strtolower(implode(' ', [
            (string) ($data['first_name'] ?? ''),
            (string) ($data['last_name'] ?? ''),
            (string) ($data['apartment_number'] ?? ''),
        ]));

        if (str_contains($haystack, 'http://') || str_contains($haystack, 'https://') || str_contains($haystack, 'www.')) {
            throw ValidationException::withMessages([
                'first_name' => [__('Please enter valid personal details.')],
            ]);
        }

        if (preg_match('/(.)\1{6,}/u', $haystack) === 1) {
            throw ValidationException::withMessages([
                'first_name' => [__('Please enter valid personal details.')],
            ]);
        }
    }
}
