<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\BuildingRole;
use App\Enums\TicketStatus;
use App\Models\Building;
use App\Models\Ticket;
use App\Models\User;

final class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->buildings()->exists() || $user->is_super_admin;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->isBuildingAdmin($ticket->building_id)) {
            return true;
        }

        if ($ticket->reported_by === $user->getKey()) {
            return true;
        }

        if ($ticket->assigned_to === $user->getKey()) {
            return true;
        }

        $isApartmentTenant = $ticket->apartment?->tenants()->whereKey($user->getKey())->exists() ?? false;

        if ($isApartmentTenant) {
            return true;
        }

        // Public tickets are visible to any tenant of the same building.
        if ($ticket->isPublic() && $user->belongsToBuilding((int) $ticket->building_id)) {
            return true;
        }

        return false;
    }

    public function create(User $user, ?Building $building = null): bool
    {
        if ($building === null) {
            return $user->isSuperAdmin() || $user->buildings()->exists();
        }

        return $user->belongsToBuilding($building->getKey());
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->isBuildingAdmin($ticket->building_id)) {
            return true;
        }

        return $ticket->reported_by === $user->getKey() && $ticket->status === TicketStatus::New;
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        // Anonymous public-visibility viewers (other tenants in the building who
        // are neither reporter, assignee, nor manager) can browse public tickets
        // but cannot post on them — comments are reserved for participants.
        if ($user->isBuildingAdmin($ticket->building_id)) {
            return true;
        }

        if ($ticket->reported_by === $user->getKey()) {
            return true;
        }

        if ($ticket->assigned_to === $user->getKey()) {
            return true;
        }

        return $ticket->apartment?->tenants()->whereKey($user->getKey())->exists() ?? false;
    }

    /**
     * Whether the user may toggle the "I have this issue too" affected flag on a public ticket.
     */
    public function markAffected(User $user, Ticket $ticket): bool
    {
        if (! $ticket->isPublic()) {
            return false;
        }

        // Building managers oversee, they don't claim to be affected.
        if ($user->isBuildingAdmin($ticket->building_id)) {
            return false;
        }

        // The reporter is implicitly affected.
        if ($ticket->reported_by === $user->getKey()) {
            return false;
        }

        return $user->belongsToBuilding((int) $ticket->building_id);
    }
}