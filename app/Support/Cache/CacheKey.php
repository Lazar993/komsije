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

    public static function userUnreadAnnouncements(int $userId, int $buildingId): string
    {
        return "user:{$userId}:building:{$buildingId}:unread_announcements";
    }
}