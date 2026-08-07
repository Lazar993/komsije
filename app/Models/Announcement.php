<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['building_id', 'author_id', 'title', 'content', 'link_url', 'links', 'is_important', 'published_at'])]
class Announcement extends Model
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

    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(AnnouncementAttachment::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_important' => 'boolean',
            'links' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function resolvedLinks(): array
    {
        $links = [];

        foreach ((array) $this->links as $value) {
            if (is_array($value)) {
                $value = $value['url'] ?? null;
            }

            if (! is_string($value)) {
                continue;
            }

            $url = trim($value);

            if ($url === '') {
                continue;
            }

            $links[] = $url;
        }

        $legacyLink = trim((string) ($this->link_url ?? ''));

        if ($legacyLink !== '') {
            array_unshift($links, $legacyLink);
        }

        return array_values(array_unique($links));
    }
}