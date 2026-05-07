<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketVisibility;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'building_id',
    'apartment_id',
    'reported_by',
    'assigned_to',
    'title',
    'description',
    'status',
    'priority',
    'visibility',
    'affected_count',
    'resolved_at',
])]
class Ticket extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class)->latest();
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(TicketStatusHistory::class)->latest();
    }

    public function affectedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ticket_affected_users')->withTimestamps();
    }

    public function isPrivate(): bool
    {
        return $this->visibility === TicketVisibility::Private;
    }

    public function isPublic(): bool
    {
        return $this->visibility === TicketVisibility::Public;
    }

    public function scopePrivateOnly(Builder $query): Builder
    {
        return $query->where('visibility', TicketVisibility::Private->value);
    }

    public function scopePublicOnly(Builder $query): Builder
    {
        return $query->where('visibility', TicketVisibility::Public->value);
    }

    /**
     * Returns true when the given viewer is allowed to see the reporter's
     * personal information (name, apartment number, attachments).
     */
    public function viewerCanSeeIdentity(?User $viewer): bool
    {
        if ($viewer === null) {
            return false;
        }

        if ($this->isPrivate()) {
            return true;
        }

        if ($viewer->isBuildingAdmin($this->building_id)) {
            return true;
        }

        if ($this->reported_by === $viewer->getKey()) {
            return true;
        }

        if ($this->assigned_to === $viewer->getKey()) {
            return true;
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'resolved_at' => 'datetime',
            'status' => TicketStatus::class,
            'visibility' => TicketVisibility::class,
        ];
    }
}