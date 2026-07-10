<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['building_id', 'milestone', 'sent_at'])]
class BuildingTrialReminder extends Model
{
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'milestone' => 'integer',
            'sent_at' => 'datetime',
        ];
    }
}
