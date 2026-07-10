<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Building;
use Illuminate\Support\Carbon;

/**
 * Derives a 0–100 composite engagement score for a building from its analytics
 * metrics, plus a human-readable rating band.
 */
final class BuildingHealthScoreService
{
    public function __construct(private readonly BuildingAnalyticsService $analytics)
    {
    }

    /**
     * @return array{score: int, rating: string, color: string, breakdown: array<string, int>}
     */
    public function score(Building $building): array
    {
        $metrics = $this->analytics->metrics($building);

        $breakdown = [
            // Adoption: how many invited tenants actually registered (25 pts).
            'adoption' => (int) round($metrics['acceptance_rate'] / 100 * 25),
            // Engagement: monthly active users vs registered users (30 pts).
            'engagement' => $this->ratio($metrics['active_users'], $metrics['registered_users'], 30),
            // Reachability: push-ready users vs registered users (15 pts).
            'reachability' => (int) round($metrics['push_delivery_rate'] / 100 * 15),
            // Content: any tickets/announcements/polls activity (20 pts).
            'content' => $this->contentScore($metrics),
            // Recency: activity within the last 30 days (10 pts).
            'recency' => $this->recencyScore($metrics['last_activity_at']),
        ];

        $score = (int) max(0, min(100, array_sum($breakdown)));

        return [
            'score' => $score,
            'rating' => $this->rating($score),
            'color' => $this->color($score),
            'breakdown' => $breakdown,
        ];
    }

    public function rating(int $score): string
    {
        return match (true) {
            $score >= 80 => __('Excellent'),
            $score >= 60 => __('Good'),
            $score >= 30 => __('Needs Attention'),
            default => __('Inactive'),
        };
    }

    public function color(int $score): string
    {
        return match (true) {
            $score >= 80 => 'success',
            $score >= 60 => 'info',
            $score >= 30 => 'warning',
            default => 'danger',
        };
    }

    private function ratio(int $part, int $whole, int $max): int
    {
        if ($whole <= 0) {
            return 0;
        }

        return (int) round(min(1, $part / $whole) * $max);
    }

    /**
     * @param array<string, mixed> $metrics
     */
    private function contentScore(array $metrics): int
    {
        $signals = [
            $metrics['total_tickets'] > 0,
            $metrics['announcements'] > 0,
            $metrics['polls'] > 0,
            $metrics['comments'] > 0,
        ];

        $active = count(array_filter($signals));

        return (int) round($active / count($signals) * 20);
    }

    private function recencyScore(?string $lastActivityAt): int
    {
        if ($lastActivityAt === null) {
            return 0;
        }

        $days = Carbon::parse($lastActivityAt)->diffInDays(Carbon::now());

        return match (true) {
            $days <= 7 => 10,
            $days <= 14 => 7,
            $days <= 30 => 4,
            default => 0,
        };
    }
}
