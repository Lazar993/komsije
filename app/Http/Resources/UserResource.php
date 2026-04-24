<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\BuildingRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pivotRole = $this->whenPivotLoaded('building_user', function (): ?string {
            $role = $this->pivot?->role;

            return $role instanceof BuildingRole ? $role->value : $role;
        });

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_super_admin' => $this->is_super_admin,
            'role' => $pivotRole,
            'buildings' => BuildingResource::collection($this->whenLoaded('buildings')),
            'apartments' => ApartmentResource::collection($this->whenLoaded('apartments')),
        ];
    }
}