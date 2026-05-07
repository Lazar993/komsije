<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketVisibility: string
{
    case Private = 'private';
    case Public = 'public';

    public function label(): string
    {
        return match ($this) {
            self::Private => __('Private'),
            self::Public => __('Public'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Private => __('Only you and the building manager can see this ticket.'),
            self::Public => __('Visible to residents of your building without revealing your personal information.'),
        };
    }
}
