<?php

declare(strict_types=1);

namespace App\Support\Notifications;

use Illuminate\Notifications\DatabaseNotification;

final class NotificationPresenter
{
    /**
     * @return array{id: string, type: string, title: string, message: string, icon: string, url: string, created_at: \Illuminate\Support\Carbon|null, is_read: bool}
     */
    public function present(DatabaseNotification $notification): array
    {
        /** @var array<string, mixed> $data */
        $data = $notification->data ?? [];
        $type = (string) ($data['type'] ?? '');

        return [
            'id' => (string) $notification->id,
            'type' => $type,
            'title' => (string) ($data['title'] ?? __('Notifikacija')),
            'message' => (string) ($data['message'] ?? ''),
            'icon' => $this->icon($type),
            'url' => $this->url($type, $data),
            'created_at' => $notification->created_at,
            'is_read' => $notification->read_at !== null,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function url(string $type, array $data): string
    {
        return match (true) {
            str_starts_with($type, 'announcement') && isset($data['announcement_id'])
                => route('portal.announcements.show', $data['announcement_id']),
            $type === 'neighbor_board_post_created' && isset($data['post_id'])
                => route('portal.neighbor-board.show', $data['post_id']),
            str_contains($type, 'ticket') && isset($data['ticket_id'])
                => route('portal.tickets.show', $data['ticket_id']),
            str_starts_with($type, 'poll')
                => route('portal.dashboard'),
            is_string($data['url'] ?? null) && $data['url'] !== ''
                => $data['url'],
            default => route('portal.dashboard'),
        };
    }

    private function icon(string $type): string
    {
        return match (true) {
            str_starts_with($type, 'announcement') => 'announcements',
            $type === 'neighbor_board_post_created' => 'neighbor-board',
            str_contains($type, 'ticket') => 'tickets',
            str_starts_with($type, 'poll') => 'home',
            default => 'bell',
        };
    }
}
