<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\NeighborBoardPostStatus;
use App\Models\NeighborBoardPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NeighborBoardArchiveCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_archives_active_posts_older_than_30_days_and_resolved_older_than_7_days(): void
    {
        $activeOld = NeighborBoardPost::factory()->create([
            'status' => NeighborBoardPostStatus::Active,
            'created_at' => now()->subDays(31),
        ]);

        $resolvedOld = NeighborBoardPost::factory()->create([
            'status' => NeighborBoardPostStatus::Resolved,
            'resolved_at' => now()->subDays(8),
        ]);

        $activeRecent = NeighborBoardPost::factory()->create([
            'status' => NeighborBoardPostStatus::Active,
            'created_at' => now()->subDays(5),
        ]);

        $resolvedRecent = NeighborBoardPost::factory()->create([
            'status' => NeighborBoardPostStatus::Resolved,
            'resolved_at' => now()->subDays(2),
        ]);

        $this->artisan('neighbor-board:archive-posts')
            ->expectsOutput('Archived 1 active and 1 resolved posts.')
            ->assertSuccessful();

        $this->assertDatabaseHas('neighbor_board_posts', [
            'id' => $activeOld->getKey(),
            'status' => NeighborBoardPostStatus::Archived->value,
        ]);

        $this->assertDatabaseHas('neighbor_board_posts', [
            'id' => $resolvedOld->getKey(),
            'status' => NeighborBoardPostStatus::Archived->value,
        ]);

        $this->assertDatabaseHas('neighbor_board_posts', [
            'id' => $activeRecent->getKey(),
            'status' => NeighborBoardPostStatus::Active->value,
        ]);

        $this->assertDatabaseHas('neighbor_board_posts', [
            'id' => $resolvedRecent->getKey(),
            'status' => NeighborBoardPostStatus::Resolved->value,
        ]);

        $this->assertDatabaseHas('neighbor_board_posts', [
            'id' => $activeOld->getKey(),
            'deleted_at' => null,
        ]);
    }
}
