<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BuildingRole;
use App\Models\Pivots\ApartmentUser;
use App\Models\Pivots\BuildingUser;
use App\Support\Localization\SiteLocale;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'is_super_admin', 'locale', 'profile_image_path'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasLocalePreference
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected string $guard_name = 'web';

    public function buildings(): BelongsToMany
    {
        return $this->belongsToMany(Building::class)
            ->using(BuildingUser::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function apartments(): BelongsToMany
    {
        return $this->belongsToMany(Apartment::class)
            ->using(ApartmentUser::class)
            ->withTimestamps();
    }

    public function reportedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'reported_by');
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function ticketComments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    public function authoredAnnouncements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'author_id');
    }

    public function announcementReads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    public function createdInvites(): HasMany
    {
        return $this->hasMany(Invite::class, 'created_by');
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    /**
     * Route notifications for the FCM channel.
     *
     * @return array<int, string>
     */
    public function routeNotificationForFcm(): array
    {
        return $this->deviceTokens()->pluck('token')->all();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'admin') {
            return false;
        }

        return $this->isSuperAdmin() || $this->isBuildingAdmin();
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin || $this->hasGlobalRole('super_admin');
    }

    public function isBuildingAdmin(?int $buildingId = null): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($buildingId === null) {
            return $this->buildings()
                ->wherePivot('role', BuildingRole::PropertyManager->value)
                ->exists();
        }

        if ($this->hasRoleInBuilding('admin', $buildingId)) {
            return true;
        }

        return $this->buildings()
            ->whereKey($buildingId)
            ->wherePivot('role', BuildingRole::PropertyManager->value)
            ->exists();
    }

    public function isTenant(?int $buildingId = null): bool
    {
        if ($buildingId === null) {
            return $this->buildings()
                ->wherePivot('role', BuildingRole::Tenant->value)
                ->exists();
        }

        if ($this->hasRoleInBuilding('tenant', $buildingId)) {
            return true;
        }

        return $this->buildings()
            ->whereKey($buildingId)
            ->wherePivot('role', BuildingRole::Tenant->value)
            ->exists();
    }

    /**
     * @return list<int>
     */
    public function managedBuildingIds(): array
    {
        if ($this->isSuperAdmin()) {
            return Building::query()->pluck('id')->map(fn ($id): int => (int) $id)->all();
        }

        return $this->buildings()
            ->wherePivot('role', BuildingRole::PropertyManager->value)
            ->pluck('buildings.id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    public function belongsToBuilding(int $buildingId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->buildings()->whereKey($buildingId)->exists();
    }

    public function hasBuildingRole(int $buildingId, BuildingRole $role): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->hasRoleInBuilding($role->permissionRoleName(), $buildingId)) {
            return true;
        }

        return $this->buildings()
            ->whereKey($buildingId)
            ->wherePivot('role', $role->value)
            ->exists();
    }

    public function buildingRole(int $buildingId): ?BuildingRole
    {
        if ($this->isSuperAdmin()) {
            return null;
        }

        if ($this->hasRoleInBuilding('admin', $buildingId)) {
            return BuildingRole::PropertyManager;
        }

        if ($this->hasRoleInBuilding('tenant', $buildingId)) {
            return BuildingRole::Tenant;
        }

        $role = $this->buildings()
            ->whereKey($buildingId)
            ->first()?->pivot?->role;

        return $role instanceof BuildingRole ? $role : ($role !== null ? BuildingRole::from($role) : null);
    }

    public function hasGlobalRole(string $role): bool
    {
        if ($role === 'super_admin') {
            return (bool) $this->is_super_admin;
        }

        return $this->usingPermissionTeam(null, fn (): bool => $this->hasRole($role));
    }

    public function hasRoleInBuilding(string $role, int $buildingId): bool
    {
        return $this->usingPermissionTeam($buildingId, fn (): bool => $this->hasRole($role));
    }

    public function syncGlobalRoles(array $roles): void
    {
        $shouldBeSuperAdmin = in_array('super_admin', $roles, true);

        if ((bool) $this->is_super_admin === $shouldBeSuperAdmin) {
            return;
        }

        $this->forceFill(['is_super_admin' => $shouldBeSuperAdmin])->saveQuietly();
    }

    public function syncBuildingRole(int $buildingId, ?string $role): void
    {
        $this->usingPermissionTeam($buildingId, function () use ($role): void {
            if ($role === null) {
                $this->syncRoles([]);

                return;
            }

            $this->syncRoles([$role]);
        });
    }

    public function siteLocale(): string
    {
        return SiteLocale::sanitize($this->locale);
    }

    public function preferredLocale(): string
    {
        return SiteLocale::appLocale($this->siteLocale());
    }

    public function profileImageUrl(): ?string
    {
        if (blank($this->profile_image_path)) {
            return null;
        }

        return rtrim((string) config('filesystems.disks.public.url'), '/') . '/' . ltrim((string) $this->profile_image_path, '/');
    }

    private function usingPermissionTeam(int|string|Model|null $teamId, callable $callback): mixed
    {
        $originalTeamId = getPermissionsTeamId();

        setPermissionsTeamId($teamId);
        $this->unsetRelation('roles');
        $this->unsetRelation('permissions');

        try {
            return $callback();
        } finally {
            setPermissionsTeamId($originalTeamId);
            $this->unsetRelation('roles');
            $this->unsetRelation('permissions');
        }
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_super_admin' => 'boolean',
            'password' => 'hashed',
        ];
    }
}
