<?php

declare(strict_types=1);

namespace App\Filament\Resources\Buildings\Pages;

use App\Filament\Resources\Buildings\BuildingResource;
use App\Models\Building;
use App\Services\BuildingService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBuilding extends EditRecord
{
    protected static string $resource = BuildingResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['manager_ids'] = $this->record->managers()->pluck('users.id')->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(BuildingService::class)->update($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
