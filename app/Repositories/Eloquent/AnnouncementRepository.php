<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Announcement;
use App\Models\User;
use App\Repositories\Contracts\AnnouncementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class AnnouncementRepository implements AnnouncementRepositoryInterface
{
    public function paginateForBuilding(int $buildingId, bool $includeDrafts, int $perPage = 15): LengthAwarePaginator
    {
        return Announcement::query()
            ->where('building_id', $buildingId)
            ->with(['author'])
            ->withCount('reads')
            ->when(! $includeDrafts, fn ($query) => $query->whereNotNull('published_at'))
            ->latest('published_at')
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function create(array $data): Announcement
    {
        return Announcement::query()->create($data);
    }

    public function update(Announcement $announcement, array $data): Announcement
    {
        $announcement->fill($data)->save();

        return $announcement->refresh();
    }

    public function markAsRead(Announcement $announcement, User $user): void
    {
        $announcement->reads()->updateOrCreate(
            ['user_id' => $user->getKey()],
            ['read_at' => now()],
        );
    }
}