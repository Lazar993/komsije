<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BuildingRole;
use App\Enums\BuildingStatus;
use App\Models\Pivots\BuildingUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Length of the automatically granted free trial, in days.
 */
#[Fillable([
    'name',
    'address',
    'created_by',
    'billing_customer_reference',
    'onboarding_token',
    'status',
    'trial_started_at',
    'trial_ends_at',
    'subscription_started_at',
    'subscription_ends_at',
    'suspended_at',
    'archived_at',
    'created_by_super_admin',
])]
class Building extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const TRIAL_DAYS = 30;

    protected static function booted(): void
    {
        static::creating(static function (Building $building): void {
            // Every building starts life as a 30-day trial unless a status was
            // explicitly provided (e.g. by data imports or tests).
            if ($building->status === null) {
                $now = Carbon::now();
                $building->status = BuildingStatus::Trial;
                $building->trial_started_at ??= $now;
                $building->trial_ends_at ??= $now->copy()->addDays(self::TRIAL_DAYS);
            }
        });

        static::deleting(static function (Building $building): void {
            if (! $building->isForceDeleting()) {
                $building->tickets()->update(['deleted_at' => now()]);
                $building->announcements()->update(['deleted_at' => now()]);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function apartments(): HasMany
    {
        return $this->hasMany(Apartment::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(BuildingUser::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function managers(): BelongsToMany
    {
        return $this->users()->wherePivot('role', BuildingRole::PropertyManager->value);
    }

    public function tenants(): BelongsToMany
    {
        return $this->users()->wherePivot('role', BuildingRole::Tenant->value);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function polls(): HasMany
    {
        return $this->hasMany(Poll::class);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(Invite::class);
    }

    public function joinRequests(): HasMany
    {
        return $this->hasMany(BuildingJoinRequest::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(BuildingAuditLog::class)->latest();
    }

    public function trialReminders(): HasMany
    {
        return $this->hasMany(BuildingTrialReminder::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Lifecycle helpers (reusable throughout the application)
    |--------------------------------------------------------------------------
    */

    public function isTrial(): bool
    {
        return $this->status === BuildingStatus::Trial;
    }

    public function isActive(): bool
    {
        return $this->status === BuildingStatus::Active;
    }

    public function isSuspended(): bool
    {
        return $this->status === BuildingStatus::Suspended;
    }

    public function isArchived(): bool
    {
        return $this->status === BuildingStatus::Archived;
    }

    /**
     * Whether the trial window has elapsed (regardless of current status).
     */
    public function isExpired(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isPast();
    }

    /**
     * Whole days remaining in the trial. Negative once expired, null when the
     * building is not on a trial timeline.
     */
    public function daysRemaining(): ?int
    {
        if ($this->trial_ends_at === null) {
            return null;
        }

        return (int) Carbon::now()->startOfDay()->diffInDays($this->trial_ends_at->copy()->startOfDay(), false);
    }

    /**
     * Trial completion as a 0–100 percentage (0 = just started, 100 = expired).
     */
    public function trialProgress(): int
    {
        if ($this->trial_started_at === null || $this->trial_ends_at === null) {
            return 0;
        }

        $total = $this->trial_started_at->diffInSeconds($this->trial_ends_at);

        if ($total <= 0) {
            return 100;
        }

        $elapsed = $this->trial_started_at->diffInSeconds(Carbon::now());

        return (int) max(0, min(100, round($elapsed / $total * 100)));
    }

    /**
     * Whether residents/managers may perform write operations. Reading history
     * is always allowed; suspended and archived buildings are read-only.
     */
    public function allowsWrites(): bool
    {
        return $this->status?->allowsWrites() ?? true;
    }

    /*
    |--------------------------------------------------------------------------
    | Query scopes
    |--------------------------------------------------------------------------
    */

    public function scopeStatus(Builder $query, BuildingStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    /**
     * Buildings still on a trial that will expire within $days days (inclusive)
     * and have not yet expired.
     */
    public function scopeTrialExpiringWithin(Builder $query, int $days): Builder
    {
        return $query
            ->where('status', BuildingStatus::Trial->value)
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [Carbon::now(), Carbon::now()->addDays($days)]);
    }

    /**
     * Trials whose end date has already passed.
     */
    public function scopeTrialExpired(Builder $query): Builder
    {
        return $query
            ->where('status', BuildingStatus::Trial->value)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', Carbon::now());
    }

    /**
     * Buildings that currently accept write operations (trial or active).
     */
    public function scopeWritable(Builder $query): Builder
    {
        return $query->whereIn('status', [
            BuildingStatus::Trial->value,
            BuildingStatus::Active->value,
        ]);
    }

    /**
     * Select options (id => name) of buildings a user may create records in.
     * Super admins see every building; managers only see the buildings they
     * manage that are not read-only (suspended/archived).
     *
     * @return array<int, string>
     */
    public static function writableSelectOptions(?User $user): array
    {
        $query = self::query()->orderBy('name');

        if ($user !== null && ! $user->isSuperAdmin()) {
            $query->whereIn('id', $user->managedBuildingIds())->writable();
        }

        return $query->pluck('name', 'id')->all();
    }

    public static function generateOnboardingToken(): string
    {
        do {
            $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        } while (self::query()->where('onboarding_token', $token)->exists());

        return Str::lower($token);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BuildingStatus::class,
            'trial_started_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'subscription_started_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
            'archived_at' => 'datetime',
            'created_by_super_admin' => 'boolean',
        ];
    }
}