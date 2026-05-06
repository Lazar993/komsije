<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Poll;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired by the scheduled poll-close job once a poll's ends_at has passed.
 */
final class PollClosed
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Poll $poll)
    {
    }
}
