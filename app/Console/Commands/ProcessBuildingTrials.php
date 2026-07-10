<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\BuildingAuditAction;
use App\Models\Building;
use App\Notifications\TrialReminderNotification;
use App\Services\BuildingLifecycleService;
use App\Services\EndOfTrialSurveyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Daily driver for the building trial lifecycle:
 *   1. Sends trial-expiry reminders to managers at 7 / 5 / 2 / 0 days.
 *   2. Generates the end-of-trial satisfaction survey on the final day.
 *   3. Suspends buildings whose trial has expired.
 *
 * All reminder deliveries are deduped via the building_trial_reminders ledger
 * and logged to the building audit trail.
 */
final class ProcessBuildingTrials extends Command
{
    protected $signature = 'buildings:process-trials';

    protected $description = 'Send trial reminders, generate the end-of-trial survey, and suspend expired trials.';

    /**
     * Days-before-expiry milestones, most-distant first.
     *
     * @var array<int, int>
     */
    private const MILESTONES = [7, 5, 2, 0];

    public function __construct(
        private readonly BuildingLifecycleService $lifecycle,
        private readonly EndOfTrialSurveyService $survey,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->sendReminders();
        $this->suspendExpiredTrials();

        return self::SUCCESS;
    }

    private function sendReminders(): void
    {
        Building::query()
            ->status(\App\Enums\BuildingStatus::Trial)
            ->whereNotNull('trial_ends_at')
            ->with(['managers', 'trialReminders'])
            ->chunkById(100, function ($buildings): void {
                foreach ($buildings as $building) {
                    $this->remindBuilding($building);
                }
            });
    }

    private function remindBuilding(Building $building): void
    {
        $daysRemaining = $building->daysRemaining();

        if ($daysRemaining === null) {
            return;
        }

        $milestone = $this->targetMilestone($daysRemaining);

        if ($milestone === null) {
            return;
        }

        $alreadySent = $building->trialReminders
            ->contains(fn ($reminder): bool => (int) $reminder->milestone === $milestone);

        if ($alreadySent) {
            return;
        }

        $managers = $building->managers;

        if ($managers->isNotEmpty()) {
            Notification::send(
                $managers,
                new TrialReminderNotification($building, max(0, $daysRemaining)),
            );
        }

        $building->trialReminders()->create([
            'milestone' => $milestone,
            'sent_at' => now(),
        ]);

        $this->lifecycle->log($building, BuildingAuditAction::ReminderSent, null, [
            'milestone' => $milestone,
            'days_remaining' => $daysRemaining,
        ]);

        // On the final day, generate the satisfaction survey (deduped internally).
        if ($milestone === 0) {
            $this->survey->generate($building);
        }

        $this->info("Trial reminder ({$milestone}d) dispatched for building #{$building->getKey()}");
    }

    /**
     * The closest upcoming reminder bucket for the given days remaining, or null
     * when the building is still outside the reminder window.
     */
    private function targetMilestone(int $daysRemaining): ?int
    {
        $candidates = array_filter(self::MILESTONES, static fn (int $m): bool => $m >= $daysRemaining);

        return $candidates === [] ? null : min($candidates);
    }

    private function suspendExpiredTrials(): void
    {
        Building::query()
            ->trialExpired()
            ->with('managers')
            ->chunkById(100, function ($buildings): void {
                foreach ($buildings as $building) {
                    $this->lifecycle->suspend($building, null, reason: 'trial_expired');
                    $this->warn("Suspended expired trial for building #{$building->getKey()}");
                }
            });
    }
}
