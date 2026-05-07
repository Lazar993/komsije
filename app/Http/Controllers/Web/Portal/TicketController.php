<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Portal;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketVisibility;
use App\Http\Requests\Ticket\StoreTicketCommentRequest;
use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketRequest;
use App\Models\Apartment;
use App\Models\Ticket;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Services\TicketService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class TicketController extends PortalController
{
    public function __construct(
        private readonly TicketService $ticketService,
        private readonly TicketRepositoryInterface $tickets,
        private readonly TenantContext $tenantContext,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Ticket::class);

        $building = $this->tenantContext->building();
        $user = $request->user();
        $isAdmin = $user->isBuildingAdmin($building->getKey());

        // "Tab" toggles the listing source for tenants between their own tickets
        // and the public building issue board. Managers always see everything.
        $tab = $request->query('tab') === 'public' ? 'public' : 'mine';

        $filters = $request->only(['status', 'priority', 'assigned_to']);

        if (! $isAdmin && $tab === 'public') {
            $tickets = $this->tickets->paginatePublicForBuilding(
                (int) $building->getKey(),
                $filters,
            )->withQueryString();
        } else {
            $tickets = $this->tickets->paginateForBuildingAndUser(
                (int) $building->getKey(),
                $user,
                $filters,
            )->withQueryString();
        }

        $managerOptions = $building->managers()->orderBy('name')->pluck('name', 'users.id');

        if ($request->ajax()) {
            return view('portal.tickets.partials.results', [
                'tickets' => $tickets,
                'tab' => $tab,
                'isAdmin' => $isAdmin,
            ]);
        }

        return $this->portalView($request, 'portal.tickets.index', [
            'managerOptions' => $managerOptions,
            'tickets' => $tickets,
            'tab' => $tab,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function create(Request $request): View
    {
        $building = $this->tenantContext->building();
        $this->authorize('create', [Ticket::class, $building]);

        return $this->portalView($request, 'portal.tickets.create', [
            'apartments' => $this->apartmentOptions($request),
            'managerOptions' => $building->managers()->orderBy('name')->pluck('name', 'users.id'),
            'priorities' => TicketPriority::cases(),
            'statuses' => TicketStatus::cases(),
            'visibilities' => TicketVisibility::cases(),
        ]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $building = $this->tenantContext->building();
        $this->authorize('create', [Ticket::class, $building]);

        $data = array_merge($request->validated(), ['building_id' => $building->getKey()]);

        if (! $request->user()->isBuildingAdmin($building->getKey())) {
            unset($data['status']);
        }

        $ticket = $this->ticketService->create($building, $request->user(), $data);

        return redirect()
            ->route('portal.tickets.show', $ticket)
            ->with('status', __('Ticket created successfully.'));
    }

    public function show(Request $request, Ticket $ticket): View|JsonResponse
    {
        abort_if($ticket->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('view', $ticket);

        $ticket->load(['apartment.tenants', 'reporter', 'assignee', 'attachments', 'comments.user', 'statusHistory.actor']);

        $user = $request->user();
        $canSeeIdentity = $ticket->viewerCanSeeIdentity($user);
        $isAffected = $ticket->isPublic()
            ? $ticket->affectedUsers()->whereKey($user->getKey())->exists()
            : false;

        if ($request->expectsJson() && $request->query('fragment') === 'conversation') {
            return response()->json($this->conversationPayload($request, $ticket));
        }

        return $this->portalView($request, 'portal.tickets.show', [
            'ticket' => $ticket,
            'canSeeIdentity' => $canSeeIdentity,
            'isAffected' => $isAffected,
        ]);
    }

    public function toggleAffected(Request $request, Ticket $ticket): RedirectResponse|JsonResponse
    {
        abort_if($ticket->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('markAffected', $ticket);

        $isAffected = $this->ticketService->toggleAffected($ticket, $request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'is_affected' => $isAffected,
                'affected_count' => $ticket->fresh()?->affected_count,
            ]);
        }

        return redirect()
            ->route('portal.tickets.show', $ticket)
            ->with('status', $isAffected
                ? __('Thanks — your manager will see that you are also affected.')
                : __('Removed from affected residents.'));
    }

    public function edit(Request $request, Ticket $ticket): View
    {
        abort_if($ticket->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('update', $ticket);

        return $this->portalView($request, 'portal.tickets.edit', [
            'apartments' => $this->apartmentOptions($request),
            'managerOptions' => $this->tenantContext->building()->managers()->orderBy('name')->pluck('name', 'users.id'),
            'priorities' => TicketPriority::cases(),
            'statuses' => TicketStatus::cases(),
            'visibilities' => TicketVisibility::cases(),
            'ticket' => $ticket,
        ]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        abort_if($ticket->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('update', $ticket);

        $data = array_merge($request->validated(), ['building_id' => $this->tenantContext->buildingId()]);

        // Tenants who are the reporter (non-admin) cannot change status; status notes are admin-only.
        if (! $request->user()->isBuildingAdmin((int) $this->tenantContext->buildingId())) {
            unset($data['status'], $data['status_note']);
        }

        $ticket = $this->ticketService->update($ticket->load('building'), $request->user(), $data);

        return redirect()
            ->route('portal.tickets.show', $ticket)
            ->with('status', __('Ticket updated successfully.'));
    }

    public function comment(StoreTicketCommentRequest $request, Ticket $ticket): RedirectResponse|JsonResponse
    {
        abort_if($ticket->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('comment', $ticket);

        $this->ticketService->addComment($ticket, $request->user(), array_merge($request->validated(), [
            'building_id' => $this->tenantContext->buildingId(),
        ]));

        if ($request->expectsJson()) {
            $ticket = $ticket->fresh();

            if ($ticket instanceof Ticket) {
                $ticket->load('comments.user');

                return response()->json($this->conversationPayload($request, $ticket));
            }
        }

        return redirect()
            ->route('portal.tickets.show', $ticket)
            ->with('status', __('Comment added.'));
    }

    /**
     * @return array{count:int,countLabel:string,html:string,latestCommentId:int|null}
     */
    private function conversationPayload(Request $request, Ticket $ticket): array
    {
        $conversation = $ticket->comments->sortBy('created_at')->values();

        return [
            'count' => $conversation->count(),
            'countLabel' => trans_choice(':count message|:count messages', $conversation->count(), ['count' => $conversation->count()]),
            'html' => view('portal.tickets.partials.conversation-feed', [
                'conversation' => $conversation,
                'currentUserId' => $request->user()?->getKey(),
                'ticket' => $ticket,
            ])->render(),
            'latestCommentId' => $conversation->last()?->getKey(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function apartmentOptions(Request $request): array
    {
        $building = $this->tenantContext->building();
        $user = $request->user();

        return Apartment::query()
            ->where('building_id', $building->getKey())
            ->when(
                ! $user->isBuildingAdmin($building->getKey()),
                fn (Builder $query): Builder => $query->whereHas('tenants', fn (Builder $tenantQuery): Builder => $tenantQuery->whereKey($user->getKey())),
            )
            ->orderBy('number')
            ->get()
            ->mapWithKeys(fn (Apartment $apartment): array => [$apartment->getKey() => __('Apartment :number', ['number' => $apartment->number])])
            ->all();
    }
}