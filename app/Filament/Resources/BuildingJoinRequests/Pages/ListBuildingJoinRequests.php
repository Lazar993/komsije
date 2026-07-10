<?php

declare(strict_types=1);

namespace App\Filament\Resources\BuildingJoinRequests\Pages;

use App\Filament\Resources\BuildingJoinRequests\BuildingJoinRequestResource;
use Filament\Resources\Pages\ListRecords;

final class ListBuildingJoinRequests extends ListRecords
{
    protected static string $resource = BuildingJoinRequestResource::class;
}
