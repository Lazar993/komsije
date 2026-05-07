<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Ticket */
class TicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $canSeeIdentity = $this->resource->viewerCanSeeIdentity($viewer);

        return [
            'id' => $this->id,
            'building_id' => $this->building_id,
            'apartment_id' => $canSeeIdentity ? $this->apartment_id : null,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status?->value,
            'priority' => $this->priority?->value,
            'visibility' => $this->visibility?->value,
            'affected_count' => (int) ($this->affected_count ?? 0),
            'is_anonymized' => ! $canSeeIdentity,
            'resolved_at' => $this->resolved_at,
            'reporter' => $canSeeIdentity ? new UserResource($this->whenLoaded('reporter')) : null,
            'reporter_label' => $canSeeIdentity
                ? ($this->reporter?->name)
                : __('Resident reported this issue'),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'apartment' => $canSeeIdentity ? new ApartmentResource($this->whenLoaded('apartment')) : null,
            'attachments' => $canSeeIdentity
                ? TicketAttachmentResource::collection($this->whenLoaded('attachments'))
                : [],
            'comments' => TicketCommentResource::collection($this->whenLoaded('comments')),
            'status_history' => TicketStatusHistoryResource::collection($this->whenLoaded('statusHistory')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}