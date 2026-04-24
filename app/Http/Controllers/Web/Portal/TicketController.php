<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Portal;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Requests\Ticket\StoreTicketCommentRequest;
use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketRequest;
use App\Models\Apartment;
use App\Models\Ticket;
use App\Services\TicketService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class TicketController extends PortalController
{
    public function __construct(
        private readonly TicketService $ticketService,
        private readonly TenantContext $tenantContext,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Ticket::class);

        $building = $this->tenantContext->building();
        $user = $request->user();

        $tickets = Ticket::query()
            ->where('building_id', $building->getKey())
            ->with(['apartment', 'reporter', 'assignee'])
            ->when($request->filled('status'), fn (Builder $query): Builder => $query->where('status', $request->string('status')->value()))
            ->when($request->filled('priority'), fn (Builder $query): Builder => $query->where('priority', $request->string('priority')->value()))
            ->when($request->filled('assigned_to'), fn (Builder $query): Builder => $query->where('assigned_to', (int) $request->integer('assigned_to')))
            ->when(
                ! $user->isBuildingAdmin($building->getKey()),
                function (Builder $query) use ($user): Builder {
                    return $query->where(function (Builder $scopedQuery) use ($user): void {
                        $scopedQuery
                            ->where('reported_by', $user->getKey())
                            ->orWhereHas('apartment.tenants', fn (Builder $tenantQuery): Builder => $tenantQuery->whereKey($user->getKey()));
                    });
                },
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $managerOptions = $building->managers()->orderBy('name')->pluck('name', 'users.id');

        if ($request->ajax()) {
            return view('portal.tickets.partials.results', [
                'tickets' => $tickets,
            ]);
        }

        return $this->portalView($request, 'portal.tickets.index', [
            'managerOptions' => $managerOptions,
            'tickets' => $tickets,
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
        ]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $building = $this->tenantContext->building();
        $this->authorize('create', [Ticket::class, $building]);

        $data = array_merge($request->validated(), ['building_id' => $building->getKey()]);

        if (! $request->user()->isBuildingAdmin($building->getKey())) {
            unset($data['assigned_to'], $data['status']);
        }

        $ticket = $this->ticketService->create($building, $request->user(), $data);

        return redirect()
            ->route('portal.tickets.show', $ticket)
            ->with('status', __('Ticket created successfully.'));
    }

    public function show(Request $request, Ticket $ticket): View
    {
        abort_if($ticket->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('view', $ticket);

        $ticket->load(['apartment.tenants', 'reporter', 'assignee', 'attachments', 'comments.user', 'statusHistory.actor']);

        return $this->portalView($request, 'portal.tickets.show', [
            'ticket' => $ticket,
        ]);
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
            'ticket' => $ticket,
        ]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        abort_if($ticket->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('update', $ticket);

        $data = array_merge($request->validated(), ['building_id' => $this->tenantContext->buildingId()]);

        $ticket = $this->ticketService->update($ticket->load('building'), $request->user(), $data);

        return redirect()
            ->route('portal.tickets.show', $ticket)
            ->with('status', __('Ticket updated successfully.'));
    }

    public function comment(StoreTicketCommentRequest $request, Ticket $ticket): RedirectResponse
    {
        abort_if($ticket->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('comment', $ticket);

        $this->ticketService->addComment($ticket, $request->user(), array_merge($request->validated(), [
            'building_id' => $this->tenantContext->buildingId(),
        ]));

        return redirect()
            ->route('portal.tickets.show', $ticket)
            ->with('status', __('Comment added.'));
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