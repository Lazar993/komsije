<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\NeighborBoardPost;
use App\Models\User;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class NeighborBoardPostCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly NeighborBoardPost $post)
    {
    }

    /**
     * Push only (plus database record), never email.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! $notifiable instanceof User || $notifiable->wantsPush()) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'neighbor_board_post_created',
            'post_id' => $this->post->getKey(),
            'building_id' => $this->post->building_id,
            'category' => $this->post->category?->label(),
            'title' => $this->post->title,
            'message' => __('New bulletin board post in :building', [
                'building' => $this->post->building->name,
            ]),
        ];
    }

    /**
     * @return array{title: string, body: string, data: array<string, scalar|null>}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => (string) $this->post->category?->label(),
            'body' => $this->post->title,
            'data' => [
                'type' => 'neighbor_board_post_created',
                'post_id' => $this->post->getKey(),
                'building_id' => $this->post->building_id,
                'url' => route('portal.neighbor-board.show', $this->post, false),
            ],
        ];
    }
}
