<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BuildingRole;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Invite;
use App\Models\User;
use App\Notifications\TenantInviteNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class InviteService
{
    public function __construct(private readonly ApartmentService $apartments)
    {
    }

    public function create(Building $building, ?Apartment $apartment, User $creator, string $email, BuildingRole $role = BuildingRole::Tenant): Invite
    {
        if (! $this->canCreateInvite($creator, $building, $role)) {
            throw new AuthorizationException(match ($role) {
                BuildingRole::PropertyManager => 'You are not allowed to invite admins to this building.',
                BuildingRole::Tenant => 'You are not allowed to invite tenants to this building.',
            });
        }

        if ($role === BuildingRole::Tenant && $apartment === null) {
            throw ValidationException::withMessages([
                'apartment_id' => [__('An apartment is required for tenant invites.')],
            ]);
        }

        if ($apartment !== null && (int) $apartment->building_id !== (int) $building->getKey()) {
            throw ValidationException::withMessages([
                'apartment_id' => [__('Selected apartment does not belong to this building.')],
            ]);
        }

        $normalizedEmail = Str::lower(trim($email));
        $apartmentId = $role === BuildingRole::Tenant ? $apartment?->getKey() : null;

        return DB::transaction(function () use ($apartmentId, $building, $creator, $normalizedEmail, $role): Invite {
            $existingInvites = Invite::query()
                ->valid()
                ->where('email', $normalizedEmail)
                ->where('building_id', $building->getKey())
                ->where('role', $role->value);

            if ($apartmentId === null) {
                $existingInvites->whereNull('apartment_id');
            } else {
                $existingInvites->where('apartment_id', $apartmentId);
            }

            $existingInvites->update(['expires_at' => now()]);

            $invite = Invite::query()->create([
                'apartment_id' => $apartmentId,
                'building_id' => $building->getKey(),
                'created_by' => $creator->getKey(),
                'email' => $normalizedEmail,
                'role' => $role->value,
            ])->load(['apartment', 'building', 'creator']);

            Notification::route('mail', $normalizedEmail)
                ->notify(new TenantInviteNotification($invite));

            return $invite;
        });
    }

    public function findByToken(string $token): ?Invite
    {
        return Invite::query()
            ->with(['apartment', 'building', 'creator'])
            ->where('token', $token)
            ->first();
    }

    public function findValidByToken(string $token): Invite
    {
        return Invite::query()
            ->with(['apartment', 'building', 'creator'])
            ->valid()
            ->where('token', $token)
            ->firstOrFail();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function accept(Invite $invite, array $data): User
    {
        return DB::transaction(function () use ($data, $invite): User {
            $lockedInvite = Invite::query()
                ->with(['apartment.building', 'building'])
                ->lockForUpdate()
                ->find($invite->getKey());

            if ($lockedInvite === null) {
                throw (new ModelNotFoundException())->setModel(Invite::class, [$invite->getKey()]);
            }

            if (! $lockedInvite->isValid()) {
                throw ValidationException::withMessages([
                    'email' => [__('This invitation is no longer valid.')],
                ]);
            }

            $normalizedEmail = Str::lower(trim((string) $data['email']));

            if (! hash_equals($lockedInvite->email, $normalizedEmail)) {
                throw ValidationException::withMessages([
                    'email' => [__('This email address does not match the invitation.')],
                ]);
            }

            $existingUser = User::query()->where('email', $normalizedEmail)->first();

            if ($existingUser !== null) {
                // Verify the caller's password to confirm identity before linking.
                if (! Hash::check((string) $data['password'], $existingUser->password)) {
                    throw ValidationException::withMessages([
                        'password' => [__('The provided password is incorrect.')],
                    ]);
                }
                $user = $existingUser;
            } else {
                if (empty(trim((string) ($data['name'] ?? '')))) {
                    throw ValidationException::withMessages([
                        'name' => [__('The name field is required.')],
                    ]);
                }
                $user = User::query()->create([
                    'email' => $normalizedEmail,
                    'locale' => 'sr',
                    'name' => trim((string) $data['name']),
                    'password' => (string) $data['password'],
                ]);
            }

            $role = BuildingRole::from((string) $lockedInvite->role);

            match ($role) {
                BuildingRole::Tenant => $this->acceptTenantInvite($lockedInvite, $user),
                BuildingRole::PropertyManager => $this->acceptPropertyManagerInvite($lockedInvite, $user),
            };

            $lockedInvite->markAsUsed();

            return $user->load(['apartments', 'buildings']);
        });
    }

    private function canCreateInvite(User $creator, Building $building, BuildingRole $role): bool
    {
        return match ($role) {
            BuildingRole::Tenant => $creator->isBuildingAdmin($building->getKey()),
            BuildingRole::PropertyManager => $creator->isSuperAdmin(),
        };
    }

    private function acceptTenantInvite(Invite $invite, User $user): void
    {
        if ($invite->apartment === null) {
            throw ValidationException::withMessages([
                'email' => [__('This invitation is missing an apartment assignment.')],
            ]);
        }

        $this->apartments->assignTenant($invite->apartment, $user);
    }

    private function acceptPropertyManagerInvite(Invite $invite, User $user): void
    {
        $building = $invite->building;

        if ($building === null || $user->isSuperAdmin()) {
            return;
        }

        $hasManagerRole = $building->users()
            ->whereKey($user->getKey())
            ->wherePivot('role', BuildingRole::PropertyManager->value)
            ->exists();

        if (! $hasManagerRole) {
            $building->users()->attach($user->getKey(), ['role' => BuildingRole::PropertyManager->value]);
        }

        $user->syncBuildingRole($building->getKey());
    }
}