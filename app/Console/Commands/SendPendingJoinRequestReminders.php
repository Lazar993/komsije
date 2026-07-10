<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\BuildingJoinRequestStatus;
use App\Models\BuildingJoinRequest;
use App\Notifications\PendingResidentJoinRequestReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

final class SendPendingJoinRequestReminders extends Command
{
    protected $signature = 'join-requests:send-reminders';

    protected $description = 'Send email reminders to managers for pending resident join requests older than 24 hours.';

    public function handle(): int
    {
        $sent = 0;

        BuildingJoinRequest::query()
            ->with(['building.managers'])
            ->where('status', BuildingJoinRequestStatus::Pending->value)
            ->whereNull('manager_reminded_at')
            ->where('created_at', '<=', now()->subDay())
            ->chunkById(100, function ($requests) use (&$sent): void {
                foreach ($requests as $joinRequest) {
                    $managers = $joinRequest->building?->managers;

                    if ($managers === null || $managers->isEmpty()) {
                        continue;
                    }

                    Notification::send($managers, new PendingResidentJoinRequestReminderNotification($joinRequest));

                    $joinRequest->forceFill([
                        'manager_reminded_at' => now(),
                    ])->save();

                    $sent++;
                }
            });

        $this->info("Pending join request reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
