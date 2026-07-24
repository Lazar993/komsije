<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NeighborBoardCategory;
use App\Enums\NeighborBoardPostStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'building_id',
    'author_id',
    'category',
    'title',
    'description',
    'status',
    'is_pinned',
    'comments_locked',
    'resolved_at',
    'archived_at',
])]
class NeighborBoardPost extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(NeighborBoardPostImage::class, 'post_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(NeighborBoardPostComment::class, 'post_id')->oldest('created_at');
    }

    public function scopeActiveStatus(Builder $query): Builder
    {
        return $query->where('status', NeighborBoardPostStatus::Active->value);
    }

    public function scopeResolvedStatus(Builder $query): Builder
    {
        return $query->where('status', NeighborBoardPostStatus::Resolved->value);
    }

    public function scopeArchivedStatus(Builder $query): Builder
    {
        return $query->where('status', NeighborBoardPostStatus::Archived->value);
    }

    public function apartmentNumberForAuthor(): ?string
    {
        $author = $this->author;

        if ($author === null) {
            return null;
        }

        if (! $author->relationLoaded('apartments')) {
            $author->load('apartments');
        }

        return $author->apartments
            ->firstWhere('building_id', (int) $this->building_id)
            ?->number;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => NeighborBoardCategory::class,
            'status' => NeighborBoardPostStatus::class,
            'is_pinned' => 'boolean',
            'comments_locked' => 'boolean',
            'resolved_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }
}
