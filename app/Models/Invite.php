<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BuildingRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Fillable(['email', 'building_id', 'apartment_id', 'role', 'token', 'expires_at', 'used_at', 'created_by'])]
class Invite extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Invite $invite): void {
            $invite->email = Str::lower(trim($invite->email));
            $invite->role ??= BuildingRole::Tenant->value;
            $invite->token ??= self::generateUniqueToken();
            $invite->expires_at ??= now()->addDays(7);
        });
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query
            ->whereNull('used_at')
            ->where('expires_at', '>', now());
    }

    public function isValid(): bool
    {
        return $this->used_at === null && $this->expires_at instanceof Carbon && $this->expires_at->isFuture();
    }

    public function markAsUsed(): void
    {
        $this->forceFill(['used_at' => now()])->save();
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    private static function generateUniqueToken(): string
    {
        do {
            $token = self::generateToken();
        } while (self::query()->where('token', $token)->exists());

        return $token;
    }
}