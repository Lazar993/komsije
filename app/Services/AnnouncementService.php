<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\AnnouncementCreated;
use App\Events\AnnouncementPublished;
use App\Models\Announcement;
use App\Models\Building;
use App\Models\User;
use App\Repositories\Contracts\AnnouncementRepositoryInterface;
use App\Support\Cache\CacheKey;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use stdClass;

final class AnnouncementService
{
    public function __construct(private readonly AnnouncementRepositoryInterface $announcements)
    {
    }

    /**
     * @return Collection<int, stdClass>
     */
    public function getLatestForBuilding(int $buildingId, bool $includeDrafts = false): Collection
    {
        if ($includeDrafts) {
            return $this->hydrateAnnouncements($this->fetchLatestAnnouncements($buildingId, true));
        }

        $announcements = Cache::remember(
            CacheKey::buildingAnnouncements($buildingId),
            now()->addMinutes(10),
            fn (): array => $this->fetchLatestAnnouncements($buildingId, false),
        );

        return $this->hydrateAnnouncements($announcements);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(Building $building, User $author, array $data): Announcement
    {
        return DB::transaction(function () use ($building, $author, $data): Announcement {
            $announcement = $this->announcements->create([
                'author_id' => $author->getKey(),
                'building_id' => $building->getKey(),
                'content' => $data['content'],
                'published_at' => $data['published_at'] ?? null,
                'title' => $data['title'],
            ]);

            $announcement->load('author')->loadCount('reads');

            event(new AnnouncementCreated($announcement));

            if ($announcement->published_at !== null) {
                event(new AnnouncementPublished($announcement));
            }

            return $announcement;
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Announcement $announcement, array $data): Announcement
    {
        return DB::transaction(function () use ($announcement, $data): Announcement {
            $wasPublished = $announcement->published_at !== null;

            $updatedAnnouncement = $this->announcements->update($announcement, [
                'content' => $data['content'] ?? $announcement->content,
                'published_at' => $data['published_at'] ?? $announcement->published_at,
                'title' => $data['title'] ?? $announcement->title,
            ]);

            $updatedAnnouncement->load('author')->loadCount('reads');

            if (! $wasPublished && $updatedAnnouncement->published_at !== null) {
                event(new AnnouncementPublished($updatedAnnouncement));
            }

            return $updatedAnnouncement;
        });
    }

    public function markAsRead(Announcement $announcement, User $user): void
    {
        $this->announcements->markAsRead($announcement, $user);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchLatestAnnouncements(int $buildingId, bool $includeDrafts): array
    {
        return Announcement::query()
            ->where('building_id', $buildingId)
            ->when(! $includeDrafts, fn ($query) => $query->whereNotNull('published_at'))
            ->with('author:id,name')
            ->latest('published_at')
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function (Announcement $announcement): array {
                return [
                    'id' => (int) $announcement->getKey(),
                    'title' => $announcement->title,
                    'content' => $announcement->content,
                    'published_at' => $announcement->published_at?->toIso8601String(),
                    'author' => $announcement->author !== null
                        ? ['id' => (int) $announcement->author->getKey(), 'name' => $announcement->author->name]
                        : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param list<array<string, mixed>> $announcements
     * @return Collection<int, stdClass>
     */
    private function hydrateAnnouncements(array $announcements): Collection
    {
        return collect($announcements)->map(function (array $announcement): stdClass {
            return (object) [
                'id' => (int) $announcement['id'],
                'title' => (string) $announcement['title'],
                'content' => (string) $announcement['content'],
                'published_at' => isset($announcement['published_at']) && $announcement['published_at'] !== null
                    ? CarbonImmutable::parse((string) $announcement['published_at'])
                    : null,
                'author' => isset($announcement['author']) && is_array($announcement['author'])
                    ? (object) $announcement['author']
                    : null,
            ];
        })->values();
    }
}