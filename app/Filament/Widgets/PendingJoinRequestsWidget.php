<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\BuildingJoinRequestStatus;
use App\Filament\Resources\BuildingJoinRequests\BuildingJoinRequestResource;
use App\Models\BuildingJoinRequest;
use App\Models\User;
use App\Services\BuildingJoinRequestService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

final class PendingJoinRequestsWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->isBuildingAdmin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Pending join requests'))
            ->description(__('Residents waiting for manager approval.'))
            ->query(function (): Builder {
                /** @var User|null $user */
                $user = Filament::auth()->user();

                if ($user === null) {
                    return BuildingJoinRequest::query()->whereRaw('1 = 0');
                }

                return BuildingJoinRequest::query()
                    ->where('status', BuildingJoinRequestStatus::Pending->value)
                    ->whereIn('building_id', $user->managedBuildingIds())
                    ->with('building')
                    ->latest();
            })
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('building.name')->label(__('Building'))->sortable(),
                TextColumn::make('apartment_number')->label(__('Apartment'))->searchable(),
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->state(fn (BuildingJoinRequest $record): string => $record->fullName()),
                TextColumn::make('created_at')->label(__('Created'))->since(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(__('Approve'))
                    ->icon(Heroicon::CheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (BuildingJoinRequest $record): void {
                        /** @var User $user */
                        $user = Filament::auth()->user();
                        app(BuildingJoinRequestService::class)->approve($record, $user);

                        Notification::make()
                            ->success()
                            ->title(__('Resident request approved'))
                            ->send();
                    }),
                Action::make('reject')
                    ->label(__('Reject'))
                    ->icon(Heroicon::XCircle)
                    ->color('danger')
                    ->schema([
                        Textarea::make('reason')
                            ->label(__('Reason (optional)'))
                            ->rows(3)
                            ->maxLength(500),
                    ])
                    ->action(function (BuildingJoinRequest $record, array $data): void {
                        /** @var User $user */
                        $user = Filament::auth()->user();
                        app(BuildingJoinRequestService::class)->reject($record, $user, $data['reason'] ?? null);

                        Notification::make()
                            ->success()
                            ->title(__('Resident request rejected'))
                            ->send();
                    }),
                Action::make('view')
                    ->label(__('View'))
                    ->icon(Heroicon::Eye)
                    ->url(fn (BuildingJoinRequest $record): string => BuildingJoinRequestResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
