<?php

declare(strict_types=1);

namespace App\Filament\Resources\NeighborBoardPosts\Pages;

use App\Filament\Resources\NeighborBoardPosts\NeighborBoardPostResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewNeighborBoardPost extends ViewRecord
{
    protected static string $resource = NeighborBoardPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
