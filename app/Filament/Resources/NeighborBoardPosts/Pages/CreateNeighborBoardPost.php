<?php

declare(strict_types=1);

namespace App\Filament\Resources\NeighborBoardPosts\Pages;

use App\Filament\Resources\NeighborBoardPosts\NeighborBoardPostResource;
use App\Models\Building;
use App\Services\NeighborBoardService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateNeighborBoardPost extends CreateRecord
{
    protected static string $resource = NeighborBoardPostResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $building = Building::query()->findOrFail($data['building_id']);

        $data['images'] = $data['images_uploads'] ?? [];
        unset($data['images_uploads']);

        return app(NeighborBoardService::class)->create($building, Auth::user(), $data);
    }
}
