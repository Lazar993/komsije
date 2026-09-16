<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case Cash = 'cash';
    case Card = 'card';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BankTransfer => __('Bank transfer'),
            self::Cash => __('Cash'),
            self::Card => __('Card'),
            self::Other => __('Other'),
        };
    }

    /**
     * @return array<string, string> value => label map for form selects.
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            static function (array $carry, self $method): array {
                $carry[$method->value] = $method->label();

                return $carry;
            },
            [],
        );
    }
}
