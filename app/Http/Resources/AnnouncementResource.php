<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Announcement */
class AnnouncementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'building_id' => $this->building_id,
            'title' => $this->title,
            'content' => $this->content,
            'published_at' => $this->published_at,
            'author' => new UserResource($this->whenLoaded('author')),
            'reads_count' => $this->whenCounted('reads'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}