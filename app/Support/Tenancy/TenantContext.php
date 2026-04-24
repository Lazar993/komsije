<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Models\Building;
use RuntimeException;

final class TenantContext
{
    private ?Building $building = null;

    public function setBuilding(Building $building): void
    {
        $this->building = $building;
    }

    public function hasBuilding(): bool
    {
        return $this->building !== null;
    }

    public function building(): Building
    {
        if ($this->building === null) {
            throw new RuntimeException('The building context has not been initialized.');
        }

        return $this->building;
    }

    public function buildingId(): int
    {
        return $this->building()->getKey();
    }
}