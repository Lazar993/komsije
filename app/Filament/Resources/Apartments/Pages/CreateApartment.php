<?php

declare(strict_types=1);

namespace App\Filament\Resources\Apartments\Pages;

use App\Filament\Resources\Apartments\ApartmentResource;
use App\Models\Building;
use App\Services\ApartmentService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateApartment extends CreateRecord
{
    protected static string $resource = ApartmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $building = Building::query()->findOrFail($data['building_id']);

        return app(ApartmentService::class)->create($building, $data);
    }
}
