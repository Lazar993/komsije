<?php

declare(strict_types=1);

namespace App\Models\Pivots;

use App\Enums\BuildingRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BuildingUser extends Pivot
{
    protected $table = 'building_user';

    public $incrementing = true;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => BuildingRole::class,
        ];
    }
}