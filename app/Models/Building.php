<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BuildingRole;
use App\Models\Pivots\BuildingUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'address', 'created_by', 'billing_customer_reference'])]
class Building extends Model
{
    use HasFactory;

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function apartments(): HasMany
    {
        return $this->hasMany(Apartment::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(BuildingUser::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function managers(): BelongsToMany
    {
        return $this->users()->wherePivot('role', BuildingRole::PropertyManager->value);
    }

    public function tenants(): BelongsToMany
    {
        return $this->users()->wherePivot('role', BuildingRole::Tenant->value);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(Invite::class);
    }
}