<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\AnnouncementCreated;
use App\Events\AnnouncementPublished;
use App\Events\TicketCreated;
use App\Events\TicketUpdated;
use App\Listeners\InvalidateBuildingAnnouncementsCache;
use App\Listeners\InvalidateUserDashboardCache;
use App\Listeners\NotifyBuildingResidentsOfAnnouncement;
use App\Listeners\NotifyManagersOfNewTicket;
use App\Listeners\NotifyTicketReporterOfUpdates;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

final class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        AnnouncementCreated::class => [
            InvalidateBuildingAnnouncementsCache::class,
        ],
        AnnouncementPublished::class => [
            InvalidateBuildingAnnouncementsCache::class,
            NotifyBuildingResidentsOfAnnouncement::class,
        ],
        TicketCreated::class => [
            InvalidateUserDashboardCache::class,
            NotifyManagersOfNewTicket::class,
        ],
        TicketUpdated::class => [
            InvalidateUserDashboardCache::class,
            NotifyTicketReporterOfUpdates::class,
        ],
    ];
}