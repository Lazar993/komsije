<?php

declare(strict_types=1);

namespace App\Filament\Resources\Announcements\Pages;

use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Models\Building;
use App\Services\AnnouncementService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $building = Building::query()->findOrFail($data['building_id']);

        $data['attachments'] = $data['attachments_uploads'] ?? [];
        unset($data['attachments_uploads']);

        return app(AnnouncementService::class)->create($building, Auth::user(), $data);
    }
}
