<?php

declare(strict_types=1);

namespace App\Enums;

enum BuildingAuditAction: string
{
    case Created = 'created';
    case TrialStarted = 'trial_started';
    case TrialExtended = 'trial_extended';
    case TrialRestarted = 'trial_restarted';
    case ExpirationChanged = 'expiration_changed';
    case ReminderSent = 'reminder_sent';
    case Suspended = 'suspended';
    case Activated = 'activated';
    case Archived = 'archived';
    case ManagerChanged = 'manager_changed';

    public function label(): string
    {
        return match ($this) {
            self::Created => __('Building created'),
            self::TrialStarted => __('Trial started'),
            self::TrialExtended => __('Trial extended'),
            self::TrialRestarted => __('Trial restarted'),
            self::ExpirationChanged => __('Expiration date changed'),
            self::ReminderSent => __('Trial reminder sent'),
            self::Suspended => __('Building suspended'),
            self::Activated => __('Building activated'),
            self::Archived => __('Building archived'),
            self::ManagerChanged => __('Manager changed'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Created, self::TrialStarted => 'heroicon-o-sparkles',
            self::TrialExtended, self::TrialRestarted, self::ExpirationChanged => 'heroicon-o-clock',
            self::ReminderSent => 'heroicon-o-bell',
            self::Suspended => 'heroicon-o-lock-closed',
            self::Activated => 'heroicon-o-check-badge',
            self::Archived => 'heroicon-o-archive-box',
            self::ManagerChanged => 'heroicon-o-user-group',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Activated, self::Created, self::TrialStarted => 'success',
            self::Suspended => 'danger',
            self::Archived => 'gray',
            self::ReminderSent => 'warning',
            default => 'info',
        };
    }
}
