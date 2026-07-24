<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\NeighborBoardPostCreated;
use App\Notifications\NeighborBoardPostCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

final class NotifyResidentsOfNeighborBoardPost implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(NeighborBoardPostCreated $event): void
    {
        if (! $event->notifyResidents) {
            return;
        }

        $post = $event->post->loadMissing('building.tenants');

        $recipients = $post->building->tenants
            ->where('id', '!=', $post->author_id)
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new NeighborBoardPostCreatedNotification($post));
    }
}
