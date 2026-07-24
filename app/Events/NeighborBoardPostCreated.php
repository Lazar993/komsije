<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\NeighborBoardPost;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class NeighborBoardPostCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly NeighborBoardPost $post,
        public readonly bool $notifyResidents,
        public readonly ?User $actor = null,
    ) {
    }
}
