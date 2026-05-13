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
}