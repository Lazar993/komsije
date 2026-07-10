<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\AnnouncementRepositoryInterface;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Repositories\Contracts\BuildingRepositoryInterface;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Repositories\Eloquent\AnnouncementRepository;
use App\Repositories\Eloquent\ApartmentRepository;
use App\Repositories\Eloquent\BuildingRepository;
use App\Repositories\Eloquent\TicketRepository;
use App\Support\Tenancy\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TenantContext::class, fn (): TenantContext => new TenantContext());

        $this->app->bind(BuildingRepositoryInterface::class, BuildingRepository::class);
        $this->app->bind(ApartmentRepositoryInterface::class, ApartmentRepository::class);
        $this->app->bind(TicketRepositoryInterface::class, TicketRepository::class);
        $this->app->bind(AnnouncementRepositoryInterface::class, AnnouncementRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('join-onboarding', static function (Request $request): array {
            $email = mb_strtolower(trim((string) $request->input('email', '')));
            $token = (string) $request->route('token', 'missing');

            return [
                Limit::perMinute(8)->by('join-ip:' . $request->ip()),
                Limit::perHour(20)->by('join-token:' . $token . '|' . $request->ip()),
                Limit::perHour(10)->by('join-email:' . $email . '|' . $request->ip()),
            ];
        });
    }
}
