<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TicketStatus;
use App\Models\Announcement;
use App\Models\Ticket;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class PruneOldContent extends Command
{
    protected $signature = 'app:prune-old-content
        {--days=30 : Age threshold in days}
        {--dry-run : Report what would be deleted without deleting}';

    protected $description = 'Delete announcements (not important) and resolved/cancelled tickets older than the given number of days.';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('--days must be a positive integer.');

            return self::INVALID;
        }

        $dryRun = (bool) $this->option('dry-run');
        $cutoff = CarbonImmutable::now()->subDays($days);

        $announcementsQuery = Announcement::query()
            ->where('is_important', false)
            ->where(function ($query) use ($cutoff): void {
                $query->where('published_at', '<', $cutoff)
                    ->orWhere(function ($inner) use ($cutoff): void {
                        $inner->whereNull('published_at')
                            ->where('created_at', '<', $cutoff);
                    });
            });

        $ticketsQuery = Ticket::query()
            ->whereIn('status', [TicketStatus::Resolved->value, TicketStatus::Cancelled->value])
            ->where(function ($query) use ($cutoff): void {
                $query->where('resolved_at', '<', $cutoff)
                    ->orWhere(function ($inner) use ($cutoff): void {
                        $inner->whereNull('resolved_at')
                            ->where('updated_at', '<', $cutoff);
                    });
            });

        $announcementsCount = (clone $announcementsQuery)->count();
        $ticketsCount = (clone $ticketsQuery)->count();

        if ($dryRun) {
            $this->info("Dry run (cutoff: {$cutoff->toDateTimeString()}).");
            $this->line("Announcements that would be deleted: {$announcementsCount}");
            $this->line("Tickets that would be deleted: {$ticketsCount}");

            return self::SUCCESS;
        }

        $deletedAnnouncements = 0;
        $announcementsQuery->chunkById(100, function ($announcements) use (&$deletedAnnouncements): void {
            foreach ($announcements as $announcement) {
                $announcement->delete();
                $deletedAnnouncements++;
            }
        });

        $deletedTickets = 0;
        $ticketsQuery->chunkById(100, function ($tickets) use (&$deletedTickets): void {
            foreach ($tickets as $ticket) {
                $ticket->delete();
                $deletedTickets++;
            }
        });

        $this->info("Deleted {$deletedAnnouncements} announcement(s) and {$deletedTickets} ticket(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
