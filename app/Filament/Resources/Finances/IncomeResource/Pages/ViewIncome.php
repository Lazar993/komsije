<?php

declare(strict_types=1);

namespace App\Filament\Resources\Finances\IncomeResource\Pages;

use App\Filament\Resources\Finances\IncomeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewIncome extends ViewRecord
{
    protected static string $resource = IncomeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
