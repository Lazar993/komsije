<?php

declare(strict_types=1);

namespace App\Filament\Resources\Buildings\RelationManagers;

use App\Models\BuildingAuditLog;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AuditLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'auditLogs';

    protected static ?string $title = 'Audit log';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-clipboard-document-list';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->isSuperAdmin();
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->modifyQueryUsing(fn ($query) => $query->with('actor'))
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('No lifecycle events yet'))
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->columns([
                TextColumn::make('action')
                    ->label(__('Event'))
                    ->badge()
                    ->formatStateUsing(fn (BuildingAuditLog $record): string => $record->action->label())
                    ->icon(fn (BuildingAuditLog $record): string => $record->action->icon())
                    ->color(fn (BuildingAuditLog $record): string => $record->action->color()),
                TextColumn::make('actor.name')
                    ->label(__('Actor'))
                    ->placeholder(__('System'))
                    ->description(fn (BuildingAuditLog $record): ?string => $record->actor?->email),
                TextColumn::make('meta')
                    ->label(__('Details'))
                    ->formatStateUsing(function (BuildingAuditLog $record): string {
                        if (empty($record->meta)) {
                            return '—';
                        }

                        return collect($record->meta)
                            ->map(fn ($value, $key): string => is_scalar($value) ? "{$key}: {$value}" : $key)
                            ->implode(', ');
                    })
                    ->wrap(),
                TextColumn::make('created_at')
                    ->label(__('When'))
                    ->dateTime()
                    ->sortable(),
            ]);
    }
}
