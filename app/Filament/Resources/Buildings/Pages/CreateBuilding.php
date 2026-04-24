<?php

declare(strict_types=1);

namespace App\Filament\Resources\Buildings\Pages;

use App\Filament\Resources\Buildings\BuildingResource;
use App\Services\BuildingService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateBuilding extends CreateRecord
{
    protected static string $resource = BuildingResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(BuildingService::class)->create($data, Auth::user());
    }
}
