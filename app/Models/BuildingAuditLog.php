<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BuildingAuditAction;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['building_id', 'actor_id', 'action', 'description', 'meta'])]
class BuildingAuditLog extends Model
{
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => BuildingAuditAction::class,
            'meta' => 'array',
        ];
    }
}
