<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NeighborBoardPostStatus;
use App\Events\NeighborBoardPostCreated;
use App\Models\Building;
use App\Models\NeighborBoardPost;
use App\Models\NeighborBoardPostComment;
use App\Models\NeighborBoardPostImage;
use App\Models\User;
use App\Repositories\Contracts\NeighborBoardPostRepositoryInterface;
use App\Support\Images\ImageResizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class NeighborBoardService
{
    public function __construct(
        private readonly NeighborBoardPostRepositoryInterface $posts,
        private readonly ImageResizer $imageResizer,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(Building $building, User $author, array $data): NeighborBoardPost
    {
        return DB::transaction(function () use ($building, $author, $data): NeighborBoardPost {
            $post = $this->posts->create([
                'building_id' => $building->getKey(),
                'author_id' => $author->getKey(),
                'category' => $data['category'],
                'title' => $data['title'],
                'description' => $data['description'],
                'status' => NeighborBoardPostStatus::Active,
                'is_pinned' => false,
                'comments_locked' => false,
                'resolved_at' => null,
                'archived_at' => null,
            ]);

            $this->storeImages($post, $author, $data['images'] ?? []);

            $post->load([
                'author.apartments' => fn ($query) => $query->select(['apartments.id', 'apartments.building_id', 'apartments.number']),
                'images',
                'comments.user',
            ])->loadCount('comments');

            event(new NeighborBoardPostCreated($post, (bool) ($data['notify_residents'] ?? false), $author));

            return $post;
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(NeighborBoardPost $post, array $data, ?User $actor = null): NeighborBoardPost
    {
        return DB::transaction(function () use ($post, $data, $actor): NeighborBoardPost {
            $this->removeImages($post, $data['remove_images'] ?? []);
            $this->ensureImageQuota($post, $data['images'] ?? []);

            $status = array_key_exists('status', $data)
                ? $this->normalizeStatus($data['status'])
                : $post->status;

            $payload = [
                'category' => $data['category'] ?? $post->category,
                'title' => $data['title'] ?? $post->title,
                'description' => $data['description'] ?? $post->description,
                'status' => $status,
            ];

            if ($status === NeighborBoardPostStatus::Resolved) {
                $payload['resolved_at'] = $post->resolved_at ?? now();
                $payload['archived_at'] = null;
            }

            if ($status === NeighborBoardPostStatus::Active) {
                $payload['resolved_at'] = null;
                $payload['archived_at'] = null;
            }

            if ($status === NeighborBoardPostStatus::Archived) {
                $payload['archived_at'] = now();
            }

            $updatedPost = $this->posts->update($post, $payload);
            $this->storeImages($updatedPost, $actor, $data['images'] ?? []);

            return $updatedPost->load([
                'author.apartments' => fn ($query) => $query->select(['apartments.id', 'apartments.building_id', 'apartments.number']),
                'images',
                'comments.user',
            ])->loadCount('comments');
        });
    }

    public function markResolved(NeighborBoardPost $post): NeighborBoardPost
    {
        return $this->posts->update($post, [
            'status' => NeighborBoardPostStatus::Resolved,
            'resolved_at' => now(),
            'archived_at' => null,
        ]);
    }

    public function archive(NeighborBoardPost $post): NeighborBoardPost
    {
        return $this->posts->update($post, [
            'status' => NeighborBoardPostStatus::Archived,
            'archived_at' => now(),
        ]);
    }

    public function restoreArchived(NeighborBoardPost $post): NeighborBoardPost
    {
        return $this->posts->update($post, [
            'status' => NeighborBoardPostStatus::Active,
            'archived_at' => null,
            'resolved_at' => null,
        ]);
    }

    public function pin(NeighborBoardPost $post, bool $isPinned): NeighborBoardPost
    {
        return $this->posts->update($post, [
            'is_pinned' => $isPinned,
        ]);
    }

    public function lockComments(NeighborBoardPost $post, bool $locked): NeighborBoardPost
    {
        return $this->posts->update($post, [
            'comments_locked' => $locked,
        ]);
    }

    public function delete(NeighborBoardPost $post): void
    {
        $post->delete();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function addComment(NeighborBoardPost $post, User $author, array $data): NeighborBoardPostComment
    {
        if ((bool) $post->comments_locked) {
            throw ValidationException::withMessages([
                'body' => [__('Comments are locked for this post.')],
            ]);
        }

        $comment = $this->posts->addComment($post, [
            'user_id' => $author->getKey(),
            'body' => $data['body'],
        ]);

        return $comment->load('user');
    }

    /**
     * @return array{active_archived:int,resolved_archived:int}
     */
    public function autoArchiveExpired(): array
    {
        $now = now();
        $activeCutoff = $now->copy()->subDays(30);
        $resolvedCutoff = $now->copy()->subDays(7);

        $activeArchived = 0;
        NeighborBoardPost::query()
            ->where('status', NeighborBoardPostStatus::Active->value)
            ->where('created_at', '<=', $activeCutoff)
            ->chunkById(100, function ($posts) use (&$activeArchived, $now): void {
                foreach ($posts as $post) {
                    $post->forceFill([
                        'status' => NeighborBoardPostStatus::Archived,
                        'archived_at' => $now,
                    ])->save();

                    $activeArchived++;
                }
            });

        $resolvedArchived = 0;
        NeighborBoardPost::query()
            ->where('status', NeighborBoardPostStatus::Resolved->value)
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '<=', $resolvedCutoff)
            ->chunkById(100, function ($posts) use (&$resolvedArchived, $now): void {
                foreach ($posts as $post) {
                    $post->forceFill([
                        'status' => NeighborBoardPostStatus::Archived,
                        'archived_at' => $now,
                    ])->save();

                    $resolvedArchived++;
                }
            });

        return [
            'active_archived' => $activeArchived,
            'resolved_archived' => $resolvedArchived,
        ];
    }

    /**
     * @param array<int, UploadedFile> $images
     */
    private function storeImages(NeighborBoardPost $post, ?User $uploader, array $images): void
    {
        foreach ($images as $image) {
            if (! $image instanceof UploadedFile || ! $image->isValid()) {
                continue;
            }

            $processed = $this->imageResizer->resize($image);
            $path = $processed->store('neighbor-board/' . $post->getKey(), 'public');

            if (! is_string($path) || $path === '') {
                continue;
            }

            $post->images()->create([
                'uploaded_by' => $uploader?->getKey(),
                'disk' => 'public',
                'path' => $path,
                'original_name' => $image->getClientOriginalName(),
                'mime_type' => $processed->getMimeType(),
                'size' => (int) $processed->getSize(),
            ]);
        }
    }

    /**
     * @param array<int, int|string> $ids
     */
    private function removeImages(NeighborBoardPost $post, array $ids): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if ($ids === []) {
            return;
        }

        $images = NeighborBoardPostImage::query()
            ->where('post_id', $post->getKey())
            ->whereIn('id', $ids)
            ->get();

        foreach ($images as $image) {
            $image->deleteFile();
            $image->delete();
        }
    }

    /**
     * @param array<int, UploadedFile> $newImages
     */
    private function ensureImageQuota(NeighborBoardPost $post, array $newImages): void
    {
        if ($newImages === []) {
            return;
        }

        $existingCount = $post->images()->count();
        $incomingCount = count($newImages);

        if (($existingCount + $incomingCount) > 3) {
            throw ValidationException::withMessages([
                'images' => [__('You can upload up to 3 images per post.')],
            ]);
        }
    }

    private function normalizeStatus(mixed $status): NeighborBoardPostStatus
    {
        if ($status instanceof NeighborBoardPostStatus) {
            return $status;
        }

        return NeighborBoardPostStatus::from((string) $status);
    }
}
