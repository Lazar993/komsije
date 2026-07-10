<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BuildingJoinRequestStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'building_id',
    'first_name',
    'last_name',
    'email',
    'phone',
    'apartment_number',
    'status',
    'approved_by',
    'approved_at',
    'rejected_at',
    'rejection_reason',
    'manager_reminded_at',
    'request_ip',
    'user_agent',
])]
class BuildingJoinRequest extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(static function (BuildingJoinRequest $request): void {
            $request->email = Str::lower(trim($request->email));
            $request->first_name = trim($request->first_name);
            $request->last_name = trim($request->last_name);
            $request->apartment_number = trim($request->apartment_number);
            $request->status ??= BuildingJoinRequestStatus::Pending->value;
            $request->phone = ($request->phone !== null && trim($request->phone) !== '') ? trim($request->phone) : null;
        });
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', BuildingJoinRequestStatus::Pending->value);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', BuildingJoinRequestStatus::Approved->value);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', BuildingJoinRequestStatus::Rejected->value);
    }

    public function fullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function isPending(): bool
    {
        return $this->status === BuildingJoinRequestStatus::Pending;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BuildingJoinRequestStatus::class,
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'manager_reminded_at' => 'datetime',
        ];
    }
}
