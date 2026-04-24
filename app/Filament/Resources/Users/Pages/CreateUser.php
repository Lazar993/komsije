<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $actor = Auth::user();

        $user = User::query()->create([
            'email' => $data['email'],
            'is_super_admin' => $actor?->isSuperAdmin() ? (bool) ($data['is_super_admin'] ?? false) : false,
            'name' => $data['name'],
            'password' => $data['password'],
        ]);

        $user->syncGlobalRoles($user->is_super_admin ? ['super_admin'] : []);

        UserResource::syncRelationships($user, $data);

        return $user;
    }
}
