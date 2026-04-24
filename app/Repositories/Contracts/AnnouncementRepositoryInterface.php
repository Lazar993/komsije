<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AnnouncementRepositoryInterface
{
    public function paginateForBuilding(int $buildingId, bool $includeDrafts, int $perPage = 15): LengthAwarePaginator;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Announcement;

    /**
     * @param array<string, mixed> $data
     */
    public function update(Announcement $announcement, array $data): Announcement;

    public function markAsRead(Announcement $announcement, User $user): void;
}