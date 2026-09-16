<?php

declare(strict_types=1);

namespace App\Enums;

enum IncomeCategory: string
{
    case RegularMaintenance = 'regular_maintenance';
    case InvestmentMaintenance = 'investment_maintenance';
    case Management = 'management';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::RegularMaintenance => __('Regular maintenance'),
            self::InvestmentMaintenance => __('Investment maintenance'),
            self::Management => __('Management'),
            self::Other => __('Other'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::RegularMaintenance => 'success',
            self::InvestmentMaintenance => 'info',
            self::Management => 'warning',
            self::Other => 'gray',
        };
    }

    /**
     * @return array<string, string> value => label map for form selects.
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            static function (array $carry, self $category): array {
                $carry[$category->value] = $category->label();

                return $carry;
            },
            [],
        );
    }
}
