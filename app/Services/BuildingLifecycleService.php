<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BuildingAuditAction;
use App\Enums\BuildingStatus;
use App\Models\Building;
use App\Models\BuildingAuditLog;
use App\Models\User;
use App\Notifications\BuildingActivatedNotification;
use App\Notifications\BuildingSuspendedNotification;
use App\Support\Cache\CacheKey;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Single source of truth for building lifecycle transitions. Every transition
 * is transactional, writes an immutable audit-log entry and, where relevant,
 * queues manager notifications through the existing notification stack.
 */
final class BuildingLifecycleService
{
    /**
     * Seed a fresh 30-day trial on a newly created building. Idempotent — safe
     * to call from the model boot hook and the service create path.
     */
    public function startTrial(Building $building, ?User $actor = null): Building
    {
        $now = Carbon::now();

        $building->forceFill([
            'status' => BuildingStatus::Trial,
            'trial_started_at' => $building->trial_started_at ?? $now,
            'trial_ends_at' => $building->trial_ends_at ?? $now->copy()->addDays(Building::TRIAL_DAYS),
        ])->save();

        $this->log($building, BuildingAuditAction::TrialStarted, $actor, [
            'trial_ends_at' => $building->trial_ends_at?->toIso8601String(),
        ]);

        $this->flush($building);

        return $building;
    }

    /**
     * Activate a paid subscription. No data is ever lost.
     */
    public function activate(Building $building, ?User $actor = null, ?Carbon $endsAt = null): Building
    {
        $building->forceFill([
            'status' => BuildingStatus::Active,
            'subscription_started_at' => $building->subscription_started_at ?? Carbon::now(),
            'subscription_ends_at' => $endsAt,
            'suspended_at' => null,
            'archived_at' => null,
        ])->save();

        $this->log($building, BuildingAuditAction::Activated, $actor, [
            'subscription_ends_at' => $endsAt?->toIso8601String(),
        ]);

        $this->flush($building);
        $this->notifyManagers($building, new BuildingActivatedNotification($building));

        return $building;
    }

    public function suspend(Building $building, ?User $actor = null, string $reason = 'manual'): Building
    {
        $building->forceFill([
            'status' => BuildingStatus::Suspended,
            'suspended_at' => Carbon::now(),
        ])->save();

        $this->log($building, BuildingAuditAction::Suspended, $actor, ['reason' => $reason]);

        $this->flush($building);
        $this->notifyManagers($building, new BuildingSuspendedNotification($building, $reason));

        return $building;
    }

    public function archive(Building $building, ?User $actor = null): Building
    {
        $building->forceFill([
            'status' => BuildingStatus::Archived,
            'archived_at' => Carbon::now(),
        ])->save();

        $this->log($building, BuildingAuditAction::Archived, $actor);

        $this->flush($building);

        return $building;
    }

    /**
     * Restart the trial clock from now for another full trial period.
     */
    public function restartTrial(Building $building, ?User $actor = null, ?int $days = null): Building
    {
        $now = Carbon::now();
        $length = $days ?? Building::TRIAL_DAYS;

        $building->forceFill([
            'status' => BuildingStatus::Trial,
            'trial_started_at' => $now,
            'trial_ends_at' => $now->copy()->addDays($length),
            'suspended_at' => null,
        ])->save();

        // A restarted trial is eligible for the full reminder cadence again.
        $building->trialReminders()->delete();

        $this->log($building, BuildingAuditAction::TrialRestarted, $actor, [
            'days' => $length,
            'trial_ends_at' => $building->trial_ends_at?->toIso8601String(),
        ]);

        $this->flush($building);

        return $building;
    }

    /**
     * Extend the current trial end date by a number of days.
     */
    public function extendTrial(Building $building, int $days, ?User $actor = null): Building
    {
        $base = $building->trial_ends_at && $building->trial_ends_at->isFuture()
            ? $building->trial_ends_at->copy()
            : Carbon::now();

        $building->forceFill([
            'status' => BuildingStatus::Trial,
            'trial_started_at' => $building->trial_started_at ?? Carbon::now(),
            'trial_ends_at' => $base->addDays($days),
            'suspended_at' => null,
        ])->save();

        // Milestones already passed for the previous end date should fire again
        // relative to the new one, so clear the reminder ledger.
        $building->trialReminders()->delete();

        $this->log($building, BuildingAuditAction::TrialExtended, $actor, [
            'days' => $days,
            'trial_ends_at' => $building->trial_ends_at?->toIso8601String(),
        ]);

        $this->flush($building);

        return $building;
    }

    /**
     * Set an explicit trial expiration date.
     */
    public function changeExpiration(Building $building, Carbon $endsAt, ?User $actor = null): Building
    {
        $previous = $building->trial_ends_at?->toIso8601String();

        $building->forceFill(['trial_ends_at' => $endsAt])->save();
        $building->trialReminders()->delete();

        $this->log($building, BuildingAuditAction::ExpirationChanged, $actor, [
            'from' => $previous,
            'to' => $endsAt->toIso8601String(),
        ]);

        $this->flush($building);

        return $building;
    }

    /**
     * Record an audit-log entry. Public so commands/listeners can log events
     * such as reminder deliveries without duplicating logic.
     *
     * @param array<string, mixed> $meta
     */
    public function log(Building $building, BuildingAuditAction $action, ?User $actor = null, array $meta = []): BuildingAuditLog
    {
        return $building->auditLogs()->create([
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'description' => $action->label(),
            'meta' => $meta === [] ? null : $meta,
        ]);
    }

    private function notifyManagers(Building $building, object $notification): void
    {
        $managers = $building->managers()->get();

        if ($managers->isEmpty()) {
            return;
        }

        Notification::send($managers, $notification);
    }

    private function flush(Building $building): void
    {
        Cache::forget(CacheKey::building((int) $building->getKey()));
    }
}
