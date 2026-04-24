<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\BuildingRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Building */
class BuildingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $this->whenPivotLoaded('building_user', function (): ?string {
            $pivotRole = $this->pivot?->role;

            return $pivotRole instanceof BuildingRole ? $pivotRole->value : $pivotRole;
        });

        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'created_by' => $this->created_by,
            'billing_customer_reference' => $this->billing_customer_reference,
            'role' => $role,
            'apartments_count' => $this->whenCounted('apartments'),
            'tickets_count' => $this->whenCounted('tickets'),
            'announcements_count' => $this->whenCounted('announcements'),
            'managers' => UserResource::collection($this->whenLoaded('managers')),
            'apartments' => ApartmentResource::collection($this->whenLoaded('apartments')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}