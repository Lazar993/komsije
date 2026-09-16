<?php

declare(strict_types=1);

namespace App\Filament\Resources\Finances\ExpenseResource\Pages;

use App\Filament\Resources\Finances\ExpenseResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewExpense extends ViewRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
