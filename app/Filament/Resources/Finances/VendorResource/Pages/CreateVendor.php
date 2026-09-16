<?php

declare(strict_types=1);

namespace App\Filament\Resources\Finances\VendorResource\Pages;

use App\Filament\Resources\Finances\VendorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVendor extends CreateRecord
{
    protected static string $resource = VendorResource::class;
}
