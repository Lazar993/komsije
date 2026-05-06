<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when an admin re-saves an already-published important announcement
 * AND ticks the "notify residents of update" checkbox.
 */
final class AnnouncementImportantUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Announcement $announcement,
        public readonly ?User $actor,
    ) {
    }
}
