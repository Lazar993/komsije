<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NeighborBoardCategory;
use App\Enums\NeighborBoardPostStatus;
use App\Models\Building;
use App\Models\NeighborBoardPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NeighborBoardPost>
 */
class NeighborBoardPostFactory extends Factory
{
    protected $model = NeighborBoardPost::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'building_id' => Building::factory(),
            'author_id' => User::factory(),
            'category' => fake()->randomElement(NeighborBoardCategory::cases()),
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'status' => NeighborBoardPostStatus::Active,
            'is_pinned' => false,
            'comments_locked' => false,
            'resolved_at' => null,
            'archived_at' => null,
        ];
    }
}
