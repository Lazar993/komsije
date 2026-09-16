<?php

declare(strict_types=1);

namespace App\Support\Finance;

/**
 * Consistent RSD money formatting for the financial module. Amounts are stored
 * as DECIMAL strings; formatting uses Serbian grouping (1.234,56 RSD).
 */
final class Money
{
    public const CURRENCY = 'RSD';

    public static function format(int|float|string|null $amount): string
    {
        return number_format((float) ($amount ?? 0), 2, ',', '.').' '.self::CURRENCY;
    }
}
