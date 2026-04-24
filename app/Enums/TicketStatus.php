<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::New => __('New'),
            self::InProgress => __('In Progress'),
            self::Resolved => __('Resolved'),
        };
    }
}