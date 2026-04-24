<?php

declare(strict_types=1);

namespace App\Support\Cache;

final class CacheKey
{
    public static function userDashboard(int $userId): string
    {
        return "user:{$userId}:dashboard";
    }

    public static function building(int $buildingId): string
    {
        return "building:{$buildingId}";
    }

    public static function buildingAnnouncements(int $buildingId): string
    {
        return "building:{$buildingId}:announcements";
    }
}