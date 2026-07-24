<?php

declare(strict_types=1);

namespace App\Filament\Resources\NeighborBoardPosts\Pages;

use App\Filament\Resources\NeighborBoardPosts\NeighborBoardPostResource;
use App\Services\NeighborBoardService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditNeighborBoardPost extends EditRecord
{
    protected static string $resource = NeighborBoardPostResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $data['images'] = $data['images_uploads'] ?? [];
        unset($data['images_uploads']);

        return app(NeighborBoardService::class)->update($record, $data, Auth::user());
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
