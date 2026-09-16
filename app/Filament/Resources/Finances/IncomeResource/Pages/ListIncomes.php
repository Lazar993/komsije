<?php

declare(strict_types=1);

namespace App\Filament\Resources\Finances\IncomeResource\Pages;

use App\Filament\Resources\Finances\IncomeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIncomes extends ListRecords
{
    protected static string $resource = IncomeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
