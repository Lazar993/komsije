<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\StoreTicketCommentRequest;
use App\Http\Resources\TicketCommentResource;
use App\Models\Ticket;
use App\Services\TicketService;
use App\Support\Tenancy\TenantContext;

final class TicketCommentController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService,
        private readonly TenantContext $tenantContext,
    ) {
    }

    public function store(StoreTicketCommentRequest $request, Ticket $ticket): TicketCommentResource
    {
        abort_if($ticket->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('comment', $ticket);

        return new TicketCommentResource($this->ticketService->addComment($ticket, $request->user(), $request->validated()));
    }
}