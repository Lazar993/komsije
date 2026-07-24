<?php

declare(strict_types=1);

namespace App\Filament\Resources\NeighborBoardPosts\Pages;

use App\Filament\Resources\NeighborBoardPosts\NeighborBoardPostResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNeighborBoardPosts extends ListRecords
{
    protected static string $resource = NeighborBoardPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
