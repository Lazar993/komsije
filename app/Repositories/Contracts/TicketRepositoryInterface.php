<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TicketRepositoryInterface
{
    /**
     * @param array<string, mixed> $filters
     */
    public function paginateForBuilding(int $buildingId, array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Ticket;

    /**
     * @param array<string, mixed> $data
     */
    public function update(Ticket $ticket, array $data): Ticket;

    /**
     * @param array<string, mixed> $data
     */
    public function addComment(Ticket $ticket, array $data): TicketComment;
}