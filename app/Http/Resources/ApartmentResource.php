<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Apartment */
class ApartmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'building_id' => $this->building_id,
            'number' => $this->number,
            'floor' => $this->floor,
            'available_for_marketplace' => $this->available_for_marketplace,
            'marketplace_listing_reference' => $this->marketplace_listing_reference,
            'tenants' => UserResource::collection($this->whenLoaded('tenants')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}