<?php

declare(strict_types=1);

namespace App\Enums;

enum BuildingStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case Suspended = 'suspended';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Trial => __('Trial'),
            self::Active => __('Active'),
            self::Suspended => __('Suspended'),
            self::Archived => __('Archived'),
        };
    }

    /**
     * Filament badge / UI colour token.
     */
    public function color(): string
    {
        return match ($this) {
            self::Trial => 'info',
            self::Active => 'success',
            self::Suspended => 'danger',
            self::Archived => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Trial => 'heroicon-o-clock',
            self::Active => 'heroicon-o-check-badge',
            self::Suspended => 'heroicon-o-lock-closed',
            self::Archived => 'heroicon-o-archive-box',
        };
    }

    /**
     * Whether residents/managers may perform write operations while the
     * building is in this status. Reading history is always allowed.
     */
    public function allowsWrites(): bool
    {
        return match ($this) {
            self::Trial, self::Active => true,
            self::Suspended, self::Archived => false,
        };
    }

    /**
     * @return array<string, string> value => label map for form selects.
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            static function (array $carry, self $status): array {
                $carry[$status->value] = $status->label();

                return $carry;
            },
            [],
        );
    }
}
