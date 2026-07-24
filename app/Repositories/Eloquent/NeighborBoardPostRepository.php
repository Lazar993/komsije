<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\NeighborBoardCategory;
use App\Enums\NeighborBoardPostStatus;
use App\Models\NeighborBoardPost;
use App\Models\NeighborBoardPostComment;
use App\Repositories\Contracts\NeighborBoardPostRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class NeighborBoardPostRepository implements NeighborBoardPostRepositoryInterface
{
    public function paginateForBuilding(int $buildingId, array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $status = (string) ($filters['status'] ?? 'all');
        $sort = (string) ($filters['sort'] ?? 'newest');
        $category = $filters['category'] ?? null;
        $search = trim((string) ($filters['search'] ?? ''));

        return NeighborBoardPost::query()
            ->where('building_id', $buildingId)
            ->with([
                'author.apartments' => fn ($query) => $query->select(['apartments.id', 'apartments.building_id', 'apartments.number']),
                'images',
            ])
            ->withCount('comments')
            ->when(
                in_array($status, [
                    NeighborBoardPostStatus::Active->value,
                    NeighborBoardPostStatus::Resolved->value,
                    NeighborBoardPostStatus::Archived->value,
                ], true),
                fn (Builder $query): Builder => $query->where('status', $status),
            )
            ->when(
                is_string($category) && $category !== '',
                fn (Builder $query): Builder => $query->where('category', $category),
            )
            ->when($search !== '', fn (Builder $query): Builder => $this->applySearch($query, $search))
            ->orderByDesc('is_pinned')
            ->when(
                $sort === 'oldest',
                fn (Builder $query): Builder => $query->orderBy('created_at'),
                fn (Builder $query): Builder => $query->latest('created_at'),
            )
            ->paginate($perPage);
    }

    public function create(array $data): NeighborBoardPost
    {
        return NeighborBoardPost::query()->create($data);
    }

    public function update(NeighborBoardPost $post, array $data): NeighborBoardPost
    {
        $post->fill($data)->save();

        return $post->refresh();
    }

    public function addComment(NeighborBoardPost $post, array $data): NeighborBoardPostComment
    {
        return $post->comments()->create($data);
    }

    private function applySearch(Builder $query, string $search): Builder
    {
        $needle = mb_strtolower($search);

        $matchingCategories = collect(NeighborBoardCategory::cases())
            ->filter(function (NeighborBoardCategory $category) use ($needle): bool {
                return str_contains(mb_strtolower($category->value), $needle)
                    || str_contains(mb_strtolower($category->label()), $needle);
            })
            ->map(fn (NeighborBoardCategory $category): string => $category->value)
            ->values()
            ->all();

        return $query->where(function (Builder $inner) use ($search, $matchingCategories): void {
            $inner->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");

            if ($matchingCategories !== []) {
                $inner->orWhereIn('category', $matchingCategories);
            }
        });
    }
}
