<?php

declare(strict_types=1);

namespace App\Enums;

enum NeighborBoardPostStatus: string
{
    case Active = 'active';
    case Resolved = 'resolved';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('Active'),
            self::Resolved => __('Resolved'),
            self::Archived => __('Archived'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }
}
