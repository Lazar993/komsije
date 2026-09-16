<?php

declare(strict_types=1);

namespace App\Filament\Resources\Finances\VendorResource\Pages;

use App\Filament\Resources\Finances\VendorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVendors extends ListRecords
{
    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
