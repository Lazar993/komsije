<?php

declare(strict_types=1);

namespace App\Filament\Resources\Buildings\Pages;

use App\Enums\BuildingRole;
use App\Filament\Resources\Buildings\BuildingResource;
use App\Models\Apartment;
use App\Services\BuildingOnboardingService;
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
                        ->maxLength(255),
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
            Action::make('inviteAdmin')
                ->label(__('Invite Admin'))
                ->icon('heroicon-o-user-plus')
                ->color('gray')
                ->visible(fn (): bool => Auth::user()?->isSuperAdmin() ?? false)
                ->schema([
                    TextInput::make('email')
                        ->label(__('Email'))
                        ->email()
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    $invite = app(InviteService::class)->create(
                        $this->record,
                        null,
                        Auth::user(),
                        (string) $data['email'],
                        BuildingRole::PropertyManager,
                    );

                    Notification::make()
                        ->success()
                        ->title(__('Invite link created.'))
                        ->body(__('Share this secure link with the admin: :url', ['url' => route('invite.show', $invite->token)]))
                        ->persistent()
                        ->send();
                }),
            Action::make('generateOnboardingQr')
                ->label(__('Generate QR Code'))
                ->icon('heroicon-o-qr-code')
                ->color('success')
                ->mountUsing(function (): void {
                    app(BuildingOnboardingService::class)->ensureToken($this->record);
                    $this->record->refresh();
                })
                ->modalHeading(__('Building onboarding QR'))
                ->modalDescription(__('Share this QR code in the entrance so residents can self-onboard.'))
                ->modalWidth('md')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('Close'))
                ->modalContent(function () {
                    $service = app(BuildingOnboardingService::class);

                    return view('filament.actions.buildings.onboarding-qr-modal', [
                        'building' => $this->record,
                        'token' => (string) $this->record->onboarding_token,
                        'joinUrl' => $service->joinUrl($this->record),
                        'qrDataUri' => $service->qrDataUri($this->record, 600),
                    ]);
                })
                ->extraModalFooterActions([
                    Action::make('downloadPdf')
                        ->label(__('Download PDF'))
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function () {
                            $service = app(BuildingOnboardingService::class);
                            $filename = 'komsije-onboarding-' . str($this->record->name)->slug('-') . '.pdf';

                            return response()->streamDownload(
                                function () use ($service): void {
                                    echo $service->pdf($this->record);
                                },
                                $filename,
                                ['Content-Type' => 'application/pdf'],
                            );
                        }),
                    Action::make('downloadPng')
                        ->label(__('Download PNG'))
                        ->icon('heroicon-o-photo')
                        ->action(function () {
                            $service = app(BuildingOnboardingService::class);
                            $filename = 'komsije-onboarding-' . str($this->record->name)->slug('-') . '.png';

                            return response()->streamDownload(
                                function () use ($service): void {
                                    echo $service->qrPng($this->record, 1200);
                                },
                                $filename,
                                ['Content-Type' => 'image/png'],
                            );
                        }),
                    Action::make('regenerateToken')
                        ->label(__('Regenerate Token'))
                        ->icon('heroicon-o-arrow-path')
                        ->color('danger')
                        ->visible(fn (): bool => Auth::user()?->isSuperAdmin() ?? false)
                        ->requiresConfirmation()
                        ->modalDescription(__('Old link becomes invalid immediately.'))
                        ->action(function (): void {
                            app(BuildingOnboardingService::class)->regenerateToken($this->record);
                            $this->record->refresh();

                            Notification::make()
                                ->success()
                                ->title(__('Onboarding token regenerated'))
                                ->body(__('Previous QR link is now invalid.'))
                                ->send();
                        }),
                ]),
            EditAction::make(),
            ...BuildingResource::lifecycleActions(),
        ];
    }
}
