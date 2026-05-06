<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Poll;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired by the scheduled poll-reminder command when a poll is set to end
 * within the next ~24 hours and reminders haven't been sent yet.
 */
final class PollEndingSoon
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Poll $poll)
    {
    }
}
