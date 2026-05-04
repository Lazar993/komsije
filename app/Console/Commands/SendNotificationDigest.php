<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Models\Ticket;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

final class SendNotificationDigest extends Command
{
    protected $signature = 'notifications:send-digest {--frequency=daily : daily|weekly}';

    protected $description = 'Send aggregated email digest of recent activity to opted-in users.';

    public function handle(): int
    {
        $frequency = (string) $this->option('frequency');

        if (! in_array($frequency, ['daily', 'weekly'], true)) {
            $this->error('frequency must be daily or weekly');

            return self::INVALID;
        }

        $now = CarbonImmutable::now();
        $since = $frequency === 'weekly' ? $now->subWeek() : $now->subDay();

        $sent = 0;

        User::query()
            ->where('notify_digest', $frequency)
            ->chunkById(100, function ($users) use ($since, $now, &$sent): void {
                foreach ($users as $user) {
                    if ($this->sendFor($user, $since, $now)) {
                        $sent++;
                    }
                }
            });

        $this->info("Digest ({$frequency}) processed. Sent: {$sent}.");

        return self::SUCCESS;
    }

    private function sendFor(User $user, CarbonImmutable $since, CarbonImmutable $now): bool
    {
        $buildingIds = $user->buildings()->pluck('buildings.id')->all();

        if ($buildingIds === []) {
            return false;
        }

        $announcements = Announcement::query()
            ->whereIn('building_id', $buildingIds)
            ->whereNotNull('published_at')
            ->where('published_at', '>=', $since)
            ->orderByDesc('published_at')
            ->limit(20)
            ->get(['id', 'title', 'building_id', 'published_at']);

        $myTickets = Ticket::query()
            ->where('reported_by', $user->getKey())
            ->where('updated_at', '>=', $since)
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get(['id', 'title', 'status', 'updated_at']);

        if ($announcements->isEmpty() && $myTickets->isEmpty()) {
            return false;
        }

        NotificationFacade::send($user, new class ($announcements, $myTickets) extends BaseNotification
        {
            public function __construct(
                private readonly \Illuminate\Support\Collection $announcements,
                private readonly \Illuminate\Support\Collection $tickets,
            ) {
            }

            /**
             * @return array<int, string>
             */
            public function via(object $notifiable): array
            {
                return ['mail'];
            }

            public function toMail(object $notifiable): MailMessage
            {
                $message = (new MailMessage())
                    ->subject(__('Komšije — your activity digest'));

                if ($this->announcements->isNotEmpty()) {
                    $message->line(__('Recent announcements:'));
                    foreach ($this->announcements as $a) {
                        $message->line('• ' . $a->title);
                    }
                }

                if ($this->tickets->isNotEmpty()) {
                    $message->line(__('Updates on your tickets:'));
                    foreach ($this->tickets as $t) {
                        $message->line('• ' . $t->title . ' — ' . (string) $t->status);
                    }
                }

                return $message->line(__('Open the app for full details.'));
            }
        });

        $user->forceFill(['last_digest_sent_at' => $now])->saveQuietly();

        return true;
    }
}
