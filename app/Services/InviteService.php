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

    public function create(Building $building, Apartment $apartment, User $creator, string $email): Invite
    {
        if (! $creator->isBuildingAdmin($building->getKey())) {
            throw new AuthorizationException('You are not allowed to invite tenants to this building.');
        }

        if ((int) $apartment->building_id !== (int) $building->getKey()) {
            throw ValidationException::withMessages([
                'apartment_id' => [__('Selected apartment does not belong to this building.')],
            ]);
        }

        $normalizedEmail = Str::lower(trim($email));

        return DB::transaction(function () use ($apartment, $building, $creator, $normalizedEmail): Invite {
            Invite::query()
                ->valid()
                ->where('email', $normalizedEmail)
                ->where('building_id', $building->getKey())
                ->where('apartment_id', $apartment->getKey())
                ->where('role', BuildingRole::Tenant->value)
                ->update(['expires_at' => now()]);

            $invite = Invite::query()->create([
                'apartment_id' => $apartment->getKey(),
                'building_id' => $building->getKey(),
                'created_by' => $creator->getKey(),
                'email' => $normalizedEmail,
                'role' => BuildingRole::Tenant->value,
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

            if ($lockedInvite->apartment === null) {
                throw ValidationException::withMessages([
                    'email' => [__('This invitation is missing an apartment assignment.')],
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

            $this->apartments->assignTenant($lockedInvite->apartment, $user);
            $lockedInvite->markAsUsed();

            return $user->load(['apartments', 'buildings']);
        });
    }
}