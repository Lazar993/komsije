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

        return $ticket->apartment?->tenants()->whereKey($user->getKey())->exists() ?? false;
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
        return $this->view($user, $ticket);
    }
}