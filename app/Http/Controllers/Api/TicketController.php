<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Services\TicketService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class TicketController extends Controller
{
    public function __construct(
        private readonly TicketRepositoryInterface $tickets,
        private readonly TicketService $ticketService,
        private readonly TenantContext $tenantContext,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Ticket::class);

        return TicketResource::collection(
            $this->tickets->paginateForBuilding(
                $this->tenantContext->buildingId(),
                $request->only(['status', 'priority', 'assigned_to']),
                (int) $request->integer('per_page', 15),
            ),
        );
    }

    public function store(StoreTicketRequest $request): TicketResource
    {
        $this->authorize('create', [Ticket::class, $this->tenantContext->building()]);

        return new TicketResource($this->ticketService->create($this->tenantContext->building(), $request->user(), $request->validated()));
    }

    public function show(Ticket $ticket): TicketResource
    {
        abort_if($ticket->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('view', $ticket);

        return new TicketResource($ticket->load(['apartment.tenants', 'reporter', 'assignee', 'attachments', 'comments.user', 'statusHistory.actor']));
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): TicketResource
    {
        abort_if($ticket->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('update', $ticket);

        return new TicketResource($this->ticketService->update($ticket->load('building'), $request->user(), $request->validated()));
    }

    public function toggleAffected(Request $request, Ticket $ticket, TicketService $service): \Illuminate\Http\JsonResponse
    {
        abort_if($ticket->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('markAffected', $ticket);

        $isAffected = $service->toggleAffected($ticket, $request->user());

        return response()->json([
            'is_affected' => $isAffected,
            'affected_count' => (int) ($ticket->fresh()?->affected_count ?? 0),
        ]);
    }
}