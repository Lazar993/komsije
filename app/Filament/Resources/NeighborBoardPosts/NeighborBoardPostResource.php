<?php

declare(strict_types=1);

namespace App\Filament\Resources\NeighborBoardPosts;

use App\Enums\NeighborBoardCategory;
use App\Enums\NeighborBoardPostStatus;
use App\Filament\Resources\NeighborBoardPosts\Pages\CreateNeighborBoardPost;
use App\Filament\Resources\NeighborBoardPosts\Pages\EditNeighborBoardPost;
use App\Filament\Resources\NeighborBoardPosts\Pages\ListNeighborBoardPosts;
use App\Filament\Resources\NeighborBoardPosts\Pages\ViewNeighborBoardPost;
use App\Models\Building;
use App\Models\NeighborBoardPost;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;
use BackedEnum;

class NeighborBoardPostResource extends Resource
{
    protected static ?string $model = NeighborBoardPost::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string | UnitEnum | null $navigationGroup = 'Communications';

    protected static ?int $navigationSort = 25;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Komšijska tabla'))->schema([
                Select::make('building_id')
                    ->label(__('Building'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(fn (): array => Building::writableSelectOptions(Auth::user())),
                Select::make('category')
                    ->required()
                    ->options(NeighborBoardCategory::options()),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->required()
                    ->rows(6)
                    ->maxLength(5000),
                Select::make('status')
                    ->required()
                    ->options(NeighborBoardPostStatus::options())
                    ->default(NeighborBoardPostStatus::Active->value),
                Checkbox::make('is_pinned')
                    ->label(__('Pinned')),
                Checkbox::make('comments_locked')
                    ->label(__('Comments locked')),
                Checkbox::make('notify_residents')
                    ->label(__('Notify residents via push'))
                    ->default(false)
                    ->dehydrated(),
                FileUpload::make('images_uploads')
                    ->label(__('Images'))
                    ->multiple()
                    ->maxFiles(3)
                    ->maxSize(10240)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                    ->storeFiles(false)
                    ->dehydrated()
                    ->columnSpanFull(),
                CheckboxList::make('remove_images')
                    ->label(__('Remove existing images'))
                    ->options(fn (?NeighborBoardPost $record): array => $record
                        ? $record->images()->pluck('original_name', 'id')->all()
                        : [])
                    ->visible(fn (?NeighborBoardPost $record): bool => $record !== null && $record->images()->exists())
                    ->dehydrated()
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['building', 'author'])->withCount('comments'))
            ->defaultSort('is_pinned', 'desc')
            ->columns([
                IconColumn::make('is_pinned')
                    ->label(__('Pinned'))
                    ->boolean(),
                TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (NeighborBoardCategory|string|null $state): string => $state instanceof NeighborBoardCategory
                        ? $state->label()
                        : (is_string($state) ? NeighborBoardCategory::from($state)->label() : '-')),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('author.name')
                    ->label(__('Author'))
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (NeighborBoardPostStatus|string|null $state): string => $state instanceof NeighborBoardPostStatus
                        ? $state->label()
                        : (is_string($state) ? NeighborBoardPostStatus::from($state)->label() : '-')),
                IconColumn::make('comments_locked')
                    ->label(__('Locked'))
                    ->boolean(),
                TextColumn::make('comments_count')
                    ->label(__('Comments')),
                TextColumn::make('building.name')
                    ->label(__('Building'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(NeighborBoardPostStatus::options())
                    ->default(NeighborBoardPostStatus::Active->value),
                SelectFilter::make('category')
                    ->options(NeighborBoardCategory::options()),
                SelectFilter::make('building_id')
                    ->label(__('Building'))
                    ->options(self::accessibleBuildingOptions()),
            ])
            ->recordActions([
                Action::make('pin')
                    ->label(fn (NeighborBoardPost $record): string => $record->is_pinned ? __('Unpin') : __('Pin'))
                    ->icon('heroicon-o-map-pin')
                    ->action(function (NeighborBoardPost $record): void {
                        $record->forceFill(['is_pinned' => ! (bool) $record->is_pinned])->save();
                    }),
                Action::make('lock_comments')
                    ->label(fn (NeighborBoardPost $record): string => $record->comments_locked ? __('Unlock comments') : __('Lock comments'))
                    ->icon('heroicon-o-lock-closed')
                    ->action(function (NeighborBoardPost $record): void {
                        $record->forceFill(['comments_locked' => ! (bool) $record->comments_locked])->save();
                    }),
                Action::make('archive')
                    ->label(__('Archive'))
                    ->color('warning')
                    ->visible(fn (NeighborBoardPost $record): bool => $record->status !== NeighborBoardPostStatus::Archived)
                    ->requiresConfirmation()
                    ->action(function (NeighborBoardPost $record): void {
                        $record->forceFill([
                            'status' => NeighborBoardPostStatus::Archived,
                            'archived_at' => now(),
                        ])->save();

                        Notification::make()->success()->title(__('Post archived'))->send();
                    }),
                Action::make('restore_archived')
                    ->label(__('Restore'))
                    ->color('success')
                    ->visible(fn (NeighborBoardPost $record): bool => $record->status === NeighborBoardPostStatus::Archived)
                    ->requiresConfirmation()
                    ->action(function (NeighborBoardPost $record): void {
                        $record->forceFill([
                            'status' => NeighborBoardPostStatus::Active,
                            'archived_at' => null,
                            'resolved_at' => null,
                        ])->save();

                        Notification::make()->success()->title(__('Post restored'))->send();
                    }),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user === null || $user->isSuperAdmin()) {
            return $query;
        }

        return $query->whereIn('building_id', $user->managedBuildingIds());
    }

    /**
     * @return array<int, string>
     */
    public static function accessibleBuildingOptions(): array
    {
        $user = Auth::user();
        $query = Building::query()->orderBy('name');

        if ($user !== null && ! $user->isSuperAdmin()) {
            $query->whereIn('id', $user->managedBuildingIds());
        }

        return $query->pluck('name', 'id')->all();
    }

    public static function canView($record): bool
    {
        $user = Auth::user();

        if (! $record instanceof NeighborBoardPost || $user === null) {
            return false;
        }

        return $user->isBuildingAdmin((int) $record->building_id);
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();

        if (! $record instanceof NeighborBoardPost || $user === null) {
            return false;
        }

        return $user->can('update', $record);
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();

        if (! $record instanceof NeighborBoardPost || $user === null) {
            return false;
        }

        return $user->can('delete', $record);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNeighborBoardPosts::route('/'),
            'create' => CreateNeighborBoardPost::route('/create'),
            'edit' => EditNeighborBoardPost::route('/{record}'),
            'view' => ViewNeighborBoardPost::route('/{record}/view'),
        ];
    }
}
