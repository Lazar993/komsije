<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Pivots\ApartmentUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['building_id', 'number', 'floor', 'available_for_marketplace', 'marketplace_listing_reference'])]
class Apartment extends Model
{
    use HasFactory;

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(ApartmentUser::class)
            ->withTimestamps();
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(Invite::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'available_for_marketplace' => 'boolean',
        ];
    }
}