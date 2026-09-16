<?php

declare(strict_types=1);

namespace App\Filament\Resources\Finances\VendorResource\Pages;

use App\Filament\Resources\Finances\VendorResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewVendor extends ViewRecord
{
    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
