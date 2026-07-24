<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\NeighborBoardPost;
use App\Models\NeighborBoardPostComment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface NeighborBoardPostRepositoryInterface
{
    /**
     * @param array<string, mixed> $filters
     */
    public function paginateForBuilding(int $buildingId, array $filters, int $perPage = 10): LengthAwarePaginator;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): NeighborBoardPost;

    /**
     * @param array<string, mixed> $data
     */
    public function update(NeighborBoardPost $post, array $data): NeighborBoardPost;

    /**
     * @param array<string, mixed> $data
     */
    public function addComment(NeighborBoardPost $post, array $data): NeighborBoardPostComment;
}
