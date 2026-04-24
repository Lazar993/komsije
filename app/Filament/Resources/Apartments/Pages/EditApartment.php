<?php

declare(strict_types=1);

namespace App\Filament\Resources\Apartments\Pages;

use App\Filament\Resources\Apartments\ApartmentResource;
use App\Models\Apartment;
use App\Services\ApartmentService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditApartment extends EditRecord
{
    protected static string $resource = ApartmentResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['tenant_ids'] = $this->record->tenants()->pluck('users.id')->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(ApartmentService::class)->update($record->load('building'), $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
