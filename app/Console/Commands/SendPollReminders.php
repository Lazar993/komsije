<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Events\PollClosed;
use App\Events\PollEndingSoon;
use App\Models\Poll;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

final class SendPollReminders extends Command
{
    protected $signature = 'polls:send-reminders';

    protected $description = 'Notify tenants of polls ending soon and dispatch closure events for polls past their ends_at.';

    public function handle(): int
    {
        $now = Carbon::now();

        // 1) Polls ending in the next ~24 hours that haven't been reminded yet.
        $endingSoon = Poll::query()
            ->where('is_active', true)
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [$now, $now->copy()->addHours(24)])
            ->whereNull('ending_reminder_sent_at')
            ->get();

        foreach ($endingSoon as $poll) {
            event(new PollEndingSoon($poll));
            $poll->forceFill(['ending_reminder_sent_at' => $now])->save();
            $this->info("Reminder dispatched for poll #{$poll->getKey()}");
        }

        // 2) Polls whose end has passed but we never sent the close notification.
        $justClosed = Poll::query()
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', $now)
            ->whereNull('closed_notified_at')
            ->get();

        foreach ($justClosed as $poll) {
            event(new PollClosed($poll));
            $poll->forceFill([
                'closed_notified_at' => $now,
                'is_active' => false,
            ])->save();
            $this->info("Close notification dispatched for poll #{$poll->getKey()}");
        }

        return self::SUCCESS;
    }
}
