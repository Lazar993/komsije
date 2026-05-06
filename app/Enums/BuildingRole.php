<?php

declare(strict_types=1);

namespace App\Enums;

enum BuildingRole: string
{
    case PropertyManager = 'admin';
    case Tenant = 'tenant';

    public function label(): string
    {
        return match ($this) {
            self::PropertyManager => 'Admin',
            self::Tenant => 'Tenant',
        };
    }

    public function permissionRoleName(): string
    {
        return match ($this) {
            self::PropertyManager => 'admin',
            self::Tenant => 'tenant',
        };
    }
}