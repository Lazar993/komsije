<?php

declare(strict_types=1);

namespace App\Filament\Resources\Polls;

use App\Filament\Resources\Polls\Pages\CreatePoll;
use App\Filament\Resources\Polls\Pages\EditPoll;
use App\Filament\Resources\Polls\Pages\ListPolls;
use App\Filament\Resources\Polls\Pages\ViewPoll;
use App\Models\Building;
use App\Models\Poll;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class PollResource extends Resource
{
    protected static ?string $model = Poll::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string | UnitEnum | null $navigationGroup = 'Communications';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Poll'))->schema([
                Select::make('building_id')
                    ->label(__('Building'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(fn (): array => self::accessibleBuildingOptions()),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->rows(4)
                    ->maxLength(2000),
                Repeater::make('options')
                    ->relationship('options')
                    ->schema([
                        TextInput::make('text')
                            ->label(__('Option text'))
                            ->required()
                            ->maxLength(500),
                    ])
                    ->required()
                    ->defaultItems(2)
                    ->minItems(2)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label(__('Active'))
                    ->default(true),
                Toggle::make('is_anonymous')
                    ->label(__('Anonymous voting'))
                    ->helperText(__('If enabled, the poll keeps voters private while still preventing duplicate votes.'))
                    ->default(true),
                DateTimePicker::make('ends_at')
                    ->label(__('Ends at')),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('building'))
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('building.name')
                    ->label(__('Building'))
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_anonymous')
                    ->label(__('Anonymous'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('building.name')
                ->label(__('Building')),
            TextEntry::make('title')
                ->label(__('Title')),
            TextEntry::make('description')
                ->label(__('Description'))
                ->placeholder('—'),
            IconEntry::make('is_active')
                ->label(__('Active'))
                ->boolean(),
            IconEntry::make('is_anonymous')
                ->label(__('Anonymous'))
                ->boolean(),
            TextEntry::make('votes_total')
                ->label(__('Total votes'))
                ->state(fn (Poll $record): int => $record->votes()->count()),
            TextEntry::make('ends_at')
                ->label(__('Ends at'))
                ->dateTime(),
            RepeatableEntry::make('results')
                ->label(__('Results'))
                ->state(fn (Poll $record): array => $record->options()
                    ->withCount('votes')
                    ->orderBy('id')
                    ->get()
                    ->map(fn ($option): array => [
                        'option' => (string) $option->text,
                        'votes' => (int) $option->votes_count,
                    ])
                    ->all())
                ->schema([
                    TextEntry::make('option')
                        ->label(__('Option')),
                    TextEntry::make('votes')
                        ->label(__('Votes')),
                ])
                ->columns(2),
            RepeatableEntry::make('vote_breakdown')
                ->label(__('Who voted'))
                ->visible(fn (Poll $record): bool => ! (bool) $record->is_anonymous)
                ->state(fn (Poll $record): array => $record->votes()
                    ->with(['user:id,name,email', 'option:id,text'])
                    ->latest('created_at')
                    ->get()
                    ->map(fn ($vote): array => [
                        'voter' => $vote->user?->name ?? __('Unknown user'),
                        'email' => $vote->user?->email ?? '—',
                        'option' => $vote->option?->text ?? '—',
                        'voted_at' => $vote->created_at?->translatedFormat('d M Y, H:i') ?? '—',
                    ])
                    ->all())
                ->schema([
                    TextEntry::make('voter')
                        ->label(__('Voter')),
                    TextEntry::make('email')
                        ->label(__('Email')),
                    TextEntry::make('option')
                        ->label(__('Option')),
                    TextEntry::make('voted_at')
                        ->label(__('Voted at')),
                ])
                ->columns(4),
            TextEntry::make('anonymous_notice')
                ->label(__('Who voted'))
                ->visible(fn (Poll $record): bool => (bool) $record->is_anonymous)
                ->state(fn (): string => __('This poll is anonymous. Individual voter identities are hidden.')),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user === null || $user->isSuperAdmin()) {
            return $query;
        }

        $buildingIds = $user->managedBuildingIds();

        return $query->whereIn('building_id', $buildingIds);
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

    public static function getPages(): array
    {
        return [
            'index' => ListPolls::route('/'),
            'create' => CreatePoll::route('/create'),
            'view' => ViewPoll::route('/{record}'),
            'edit' => EditPoll::route('/{record}/edit'),
        ];
    }
}
