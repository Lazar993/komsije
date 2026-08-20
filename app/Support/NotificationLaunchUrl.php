<?php

declare(strict_types=1);

namespace App\Support;

final class NotificationLaunchUrl
{
    /**
     * @param  array<string, scalar|null>  $data
     * @return array<string, scalar|null>
     */
    public static function wrap(array $data): array
    {
        $target = self::normalize($data['url'] ?? null);

        if ($target === null) {
            return $data;
        }

        $buildingId = self::normalizeBuildingId($data['building_id'] ?? null);

        if ($buildingId !== null) {
            $target = self::appendQueryParam($target, 'building_id', (string) $buildingId);
        }

        $data['target_url'] = $target;
        $data['url'] = route('notification.launch', ['target' => $target], false);

        return $data;
    }

    public static function normalize(mixed $target): ?string
    {
        if (! is_string($target)) {
            return null;
        }

        $target = trim($target);

        if ($target === '' || ! str_starts_with($target, '/') || str_starts_with($target, '//')) {
            return null;
        }

        if (str_contains($target, '\\')) {
            return null;
        }

        return $target;
    }

    private static function normalizeBuildingId(mixed $buildingId): ?int
    {
        if (is_int($buildingId)) {
            return $buildingId > 0 ? $buildingId : null;
        }

        if (! is_string($buildingId) || ! ctype_digit($buildingId)) {
            return null;
        }

        $normalized = (int) $buildingId;

        return $normalized > 0 ? $normalized : null;
    }

    private static function appendQueryParam(string $pathWithQuery, string $key, string $value): string
    {
        $parts = parse_url($pathWithQuery);

        if (! is_array($parts)) {
            return $pathWithQuery;
        }

        $query = [];

        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        if (! array_key_exists($key, $query)) {
            $query[$key] = $value;
        }

        $path = (string) ($parts['path'] ?? '');

        if ($query !== []) {
            $path .= '?'.http_build_query($query);
        }

        if (isset($parts['fragment']) && $parts['fragment'] !== '') {
            $path .= '#'.$parts['fragment'];
        }

        return $path;
    }
}