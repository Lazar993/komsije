<?php

declare(strict_types=1);

namespace App\Filament\Resources\Announcements;

use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Resources\Announcements\Pages\ListAnnouncements;
use App\Filament\Resources\Announcements\Pages\ViewAnnouncement;
use App\Models\Announcement;
use App\Models\Building;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::Megaphone;

    protected static string | UnitEnum | null $navigationGroup = 'Communications';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Announcement')->schema([
                Select::make('building_id')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(fn (): array => self::accessibleBuildingOptions()),
                Textarea::make('title')
                    ->required()
                    ->rows(2)
                    ->maxLength(255),
                Textarea::make('content')
                    ->required()
                    ->rows(6)
                    ->maxLength(10000),
                Toggle::make('is_important')
                    ->label('Important')
                    ->helperText('Important announcements also send an email to all residents.')
                    ->default(false),
                DateTimePicker::make('published_at'),
                FileUpload::make('attachments_uploads')
                    ->label('Attachments')
                    ->helperText('Upload PDF or DOC/DOCX files (max 20 MB each, up to 10 files).')
                    ->multiple()
                    ->maxFiles(10)
                    ->maxSize(20480)
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ])
                    ->storeFiles(false)
                    ->dehydrated()
                    ->columnSpanFull(),
                CheckboxList::make('remove_attachments')
                    ->label('Remove existing attachments')
                    ->options(fn (?Announcement $record): array => $record
                        ? $record->attachments()->pluck('original_name', 'id')->all()
                        : [])
                    ->visible(fn (?Announcement $record): bool => $record !== null && $record->attachments()->exists())
                    ->dehydrated()
                    ->bulkToggleable()
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('building.name'),
            TextEntry::make('title'),
            TextEntry::make('content'),
            IconEntry::make('is_important')
                ->boolean()
                ->label('Important'),
            TextEntry::make('published_at')
                ->dateTime(),
            TextEntry::make('reads_count')
                ->label('Reads'),
            RepeatableEntry::make('attachments')
                ->label('Attachments')
                ->visible(fn (Announcement $record): bool => $record->attachments()->exists())
                ->schema([
                    TextEntry::make('original_name')
                        ->label('File'),
                    TextEntry::make('size')
                        ->label('Size')
                        ->formatStateUsing(fn (?int $state): string => $state ? round($state / 1024, 1) . ' KB' : '—'),
                    TextEntry::make('mime_type')
                        ->label('Type'),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('building')->withCount('reads'))
            ->columns([
                TextColumn::make('building.name')
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable(),
                IconColumn::make('is_important')
                    ->label('Important')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('reads_count')
                    ->label('Reads'),
            ])
            ->filters([
                TernaryFilter::make('is_important')
                    ->label('Important'),
                TernaryFilter::make('published_at')
                    ->label('Published')
                    ->nullable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user === null || $user->is_super_admin) {
            return $query;
        }

        $buildingIds = $user->buildings()->pluck('buildings.id');

        return $query->whereIn('building_id', $buildingIds);
    }

    /**
     * @return array<int, string>
     */
    public static function accessibleBuildingOptions(): array
    {
        $user = Auth::user();
        $query = Building::query()->orderBy('name');

        if ($user !== null && ! $user->is_super_admin) {
            $query->whereHas('users', fn (Builder $builder): Builder => $builder->whereKey($user->getKey()));
        }

        return $query->pluck('name', 'id')->all();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnnouncements::route('/'),
            'create' => CreateAnnouncement::route('/create'),
            'view' => ViewAnnouncement::route('/{record}'),
            'edit' => EditAnnouncement::route('/{record}/edit'),
        ];
    }
}
