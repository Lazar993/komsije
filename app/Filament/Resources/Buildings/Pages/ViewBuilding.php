<?php

declare(strict_types=1);

namespace App\Filament\Resources\Buildings\Pages;

use App\Filament\Resources\Buildings\BuildingResource;
use App\Models\Apartment;
use App\Models\User;
use App\Services\InviteService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\ViewRecord;

class ViewBuilding extends ViewRecord
{
    protected static string $resource = BuildingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('inviteTenant')
                ->label(__('Invite Tenant'))
                ->icon('heroicon-o-envelope')
                ->color('primary')
                ->schema([
                    TextInput::make('email')
                        ->label(__('Email'))
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(User::class, 'email', ignoreRecord: false),
                    Select::make('apartment_id')
                        ->label(__('Apartment'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(fn (): array => Apartment::query()
                            ->where('building_id', $this->record->getKey())
                            ->orderBy('number')
                            ->pluck('number', 'id')
                            ->all()),
                ])
                ->action(function (array $data): void {
                    $invite = app(InviteService::class)->create(
                        $this->record,
                        Apartment::query()->whereKey((int) $data['apartment_id'])->firstOrFail(),
                        Auth::user(),
                        (string) $data['email'],
                    );

                    Notification::make()
                        ->success()
                        ->title(__('Invite link created.'))
                        ->body(__('Share this secure link with the tenant: :url', ['url' => route('invite.show', $invite->token)]))
                        ->persistent()
                        ->send();
                }),
            EditAction::make(),
        ];
    }
}
