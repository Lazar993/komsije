<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Portal;

use App\Enums\TicketStatus;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\Announcement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class ProfileController extends PortalController
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $accessibleBuildings = $this->accessibleBuildings($user);
        $currentBuilding = $this->resolveCurrentBuilding($request, $accessibleBuildings);
        [$profileApartment, $profileBuilding] = $this->resolveResidence($user, $currentBuilding);

        $ticketStats = [
            'active' => 0,
            'resolved' => 0,
            'total' => 0,
        ];
        $recentAnnouncements = collect();
        $recentTickets = collect();
        $recentTicket = null;
        $recentUnreadAnnouncementsCount = 0;
        $manager = null;

        if ($profileBuilding !== null) {
            $ticketQuery = Ticket::query()
                ->where('reported_by', $user->getKey())
                ->where('building_id', $profileBuilding->getKey());

            $counts = (clone $ticketQuery)
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status');

            $ticketStats = [
                'active'   => (int) (($counts[TicketStatus::New->value] ?? 0) + ($counts[TicketStatus::InProgress->value] ?? 0)),
                'resolved' => (int) ($counts[TicketStatus::Resolved->value] ?? 0),
                'total'    => (int) $counts->sum(),
            ];

            $recentTickets = (clone $ticketQuery)
                ->with(['apartment', 'assignee', 'reporter'])
                ->latest()
                ->limit(5)
                ->get();

            $recentTicket = $recentTickets->first();

            $recentAnnouncements = Announcement::query()
                ->where('building_id', $profileBuilding->getKey())
                ->whereNotNull('published_at')
                ->with('author')
                ->withExists([
                    'reads as is_read' => fn (Builder $query): Builder => $query->where('user_id', $user->getKey()),
                ])
                ->latest('published_at')
                ->latest('created_at')
                ->limit(3)
                ->get();

            $recentUnreadAnnouncementsCount = $recentAnnouncements
                ->where('is_read', false)
                ->count();

            $manager = $profileBuilding->managers()
                ->orderBy('name')
                ->first();
        }

        return $this->portalView($request, 'portal.profile', [
            'accessibleBuildings' => $accessibleBuildings,
            'currentBuilding' => $currentBuilding,
            'manager' => $manager,
            'profileApartment' => $profileApartment,
            'profileBuilding' => $profileBuilding,
            'recentAnnouncements' => $recentAnnouncements,
            'recentTickets' => $recentTickets,
            'recentTicket' => $recentTicket,
            'recentUnreadAnnouncementsCount' => $recentUnreadAnnouncementsCount,
            'ticketStats' => $ticketStats,
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->except(['profile_image', 'remove_profile_image']);

        if ($request->boolean('remove_profile_image')) {
            $this->deleteProfileImage($user->profile_image_path);
            $data['profile_image_path'] = null;
        }

        if ($request->hasFile('profile_image')) {
            $this->deleteProfileImage($user->profile_image_path);
            $data['profile_image_path'] = $request->file('profile_image')->store('profile-images', 'public');
        }

        $user->forceFill($data)->save();

        return redirect()
            ->route('portal.profile.show')
            ->with('status', __('Profil je uspešno ažuriran.'));
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill([
            'password' => Hash::make($request->string('password')->value()),
        ])->save();

        $request->session()->put('password_hash_' . Auth::getDefaultDriver(), $user->getAuthPassword());

        return redirect()
            ->route('portal.profile.show')
            ->with('status', __('Lozinka je uspešno promenjena.'));
    }

    /**
     * @return array{0: Apartment|null, 1: Building|null}
     */
    private function resolveResidence(User $user, ?Building $currentBuilding): array
    {
        $apartment = $user->apartments()
            ->with('building')
            ->when(
                $currentBuilding !== null,
                fn (Builder $query): Builder => $query->where('building_id', $currentBuilding->getKey()),
            )
            ->orderBy('floor')
            ->orderBy('number')
            ->first();

        $building = $currentBuilding ?? $apartment?->building;

        if ($building === null) {
            $building = $user->buildings()->orderBy('name')->first();
        }

        if (($apartment === null) && ($building !== null)) {
            $apartment = $user->apartments()
                ->with('building')
                ->where('building_id', $building->getKey())
                ->orderBy('floor')
                ->orderBy('number')
                ->first();
        }

        return [$apartment, $building];
    }

    private function deleteProfileImage(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}