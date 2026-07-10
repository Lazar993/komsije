<?php

declare(strict_types=1);

namespace App\Filament\Resources\BuildingJoinRequests\Pages;

use App\Enums\BuildingJoinRequestStatus;
use App\Filament\Resources\BuildingJoinRequests\BuildingJoinRequestResource;
use App\Models\BuildingJoinRequest;
use App\Services\BuildingJoinRequestService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

final class ViewBuildingJoinRequest extends ViewRecord
{
    protected static string $resource = BuildingJoinRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label(__('Approve'))
                ->icon(Heroicon::CheckCircle)
                ->color('success')
                ->visible(fn (): bool => $this->record->status === BuildingJoinRequestStatus::Pending)
                ->requiresConfirmation()
                ->action(function (): void {
                    app(BuildingJoinRequestService::class)->approve($this->record, Auth::user());

                    Notification::make()
                        ->success()
                        ->title(__('Resident request approved'))
                        ->body(__('Invitation email has been sent using the existing invite flow.'))
                        ->send();

                    $this->record = $this->record->fresh();
                }),
            Action::make('reject')
                ->label(__('Reject'))
                ->icon(Heroicon::XCircle)
                ->color('danger')
                ->visible(fn (): bool => $this->record->status === BuildingJoinRequestStatus::Pending)
                ->schema([
                    Textarea::make('reason')
                        ->label(__('Reason (optional)'))
                        ->rows(3)
                        ->maxLength(500),
                ])
                ->action(function (array $data): void {
                    app(BuildingJoinRequestService::class)->reject($this->record, Auth::user(), $data['reason'] ?? null);

                    Notification::make()
                        ->success()
                        ->title(__('Resident request rejected'))
                        ->send();

                    $this->record = $this->record->fresh();
                }),
            Action::make('refresh')
                ->label(__('Refresh'))
                ->action(function (): void {
                    $this->record = BuildingJoinRequest::query()->findOrFail($this->record->getKey());
                }),
        ];
    }
}
