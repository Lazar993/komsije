<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Building;
use App\Models\User;
use App\Support\Notifications\NotificationPresenter;
use Illuminate\Notifications\DatabaseNotification;

final class NotificationFeedService
{
    public function __construct(private readonly NotificationPresenter $presenter)
    {
    }

    /**
     * Build a page of recent, building-scoped notifications for the header bell.
     *
     * @return array{items: \Illuminate\Support\Collection<int, array<string, mixed>>, has_more: bool, next_page: int|null}
     */
    public function feed(User $user, Building $building, int $page = 1): array
    {
        $days = max(1, (int) config('notifications.recent_days', 14));
        $perPage = max(1, (int) config('notifications.per_page', 8));

        $paginator = $user->notifications()
            ->where('created_at', '>=', now()->subDays($days))
            ->where('data->building_id', $building->getKey())
            ->paginate($perPage, ['*'], 'page', max(1, $page));

        return [
            'items' => $paginator->getCollection()
                ->map(fn (DatabaseNotification $notification): array => $this->presenter->present($notification))
                ->values(),
            'has_more' => $paginator->hasMorePages(),
            'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
        ];
    }
}
