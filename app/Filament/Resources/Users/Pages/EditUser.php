<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['manager_building_ids'] = $this->record->buildings()
            ->wherePivot('role', 'property_manager')
            ->pluck('buildings.id')
            ->all();
        $data['tenant_building_ids'] = $this->record->buildings()
            ->wherePivot('role', 'tenant')
            ->pluck('buildings.id')
            ->all();
        $data['apartment_ids'] = $this->record->apartments()->pluck('apartments.id')->all();
        $data['password'] = null;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $actor = Auth::user();

        $record->fill([
            'email' => $data['email'],
            'is_super_admin' => $actor?->isSuperAdmin() ? (bool) ($data['is_super_admin'] ?? false) : $record->is_super_admin,
            'name' => $data['name'],
        ]);

        if (! empty($data['password'])) {
            $record->password = $data['password'];
        }

        $record->save();
        $record->syncGlobalRoles($record->is_super_admin ? ['super_admin'] : []);
        UserResource::syncRelationships($record, $data);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
