<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Announcement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Ticket;
use App\Models\User;
use App\Policies\AnnouncementPolicy;
use App\Policies\ApartmentPolicy;
use App\Policies\BuildingPolicy;
use App\Policies\TicketPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::before(static fn ($user, string $ability): ?bool => $user->isSuperAdmin() ? true : null);

        Gate::policy(Building::class, BuildingPolicy::class);
        Gate::policy(Apartment::class, ApartmentPolicy::class);
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(Announcement::class, AnnouncementPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }
}