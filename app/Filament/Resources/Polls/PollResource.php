<?php

declare(strict_types=1);

namespace App\Filament\Resources\Polls;

use App\Filament\Concerns\TranslatesFilamentLabels;
use App\Filament\Resources\Polls\Pages\CreatePoll;
use App\Filament\Resources\Polls\Pages\EditPoll;
use App\Filament\Resources\Polls\Pages\ListPolls;
use App\Filament\Resources\Polls\Pages\ViewPoll;
use App\Models\Building;
use App\Models\Poll;
use App\Rules\BuildingAcceptsWrites;
use App\Services\PollReportPdfService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class PollResource extends Resource
{
    use TranslatesFilamentLabels;

    protected static ?string $model = Poll::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'Communications';

    protected static ?string $navigationLabel = 'Polls';

    protected static ?string $pluralModelLabel = 'Polls';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Poll'))->schema([
                Select::make('building_id')
                    ->label(__('Building'))
                    ->disabled(fn (?Poll $record): bool => $record?->votes()->exists() ?? false)
                    ->required()
                    ->searchable()
                    ->preload()
                    ->rules([new BuildingAcceptsWrites])
                    ->options(fn (): array => Building::writableSelectOptions(Auth::user())),
                TextInput::make('title')
                    ->label(__('Title'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label(__('Description'))
                    ->placeholder('-')
                    ->rows(4)
                    ->maxLength(2000)
                    ->columnSpanFull(),
                Toggle::make('is_decision')
                    ->label(__('Decision-making poll'))
                    ->dehydrated(false)
                    ->default(false)
                    ->live()
                    ->afterStateHydrated(fn (Toggle $component, ?Poll $record) => $component->state($record?->majority_type !== null))
                    ->afterStateUpdated(fn (Set $set, bool $state) => $set('majority_type', $state ? 'simple' : null))
                    ->disabled(fn (?Poll $record): bool => $record?->votes()->exists() ?? false),
                Select::make('majority_type')
                    ->label(__('Required majority'))
                    ->options([
                        'simple' => __('Simple majority (more than 50%)'),
                        'two_thirds' => __('Two-thirds majority (at least 2/3)'),
                    ])
                    ->visible(fn (Get $get): bool => (bool) $get('is_decision'))
                    ->required(fn (Get $get): bool => (bool) $get('is_decision'))
                    ->dehydratedWhenHidden()
                    ->dehydrateStateUsing(fn ($state, Get $get) => $get('is_decision') ? $state : null)
                    ->disabled(fn (?Poll $record): bool => $record?->votes()->exists() ?? false),
                Repeater::make('questions')
                    ->label(__('Questions'))
                    ->relationship('questions')
                    ->addActionLabel(__('Add question'))
                    ->reorderable(false)
                    ->disabled(fn (?Poll $record): bool => $record?->votes()->exists() ?? false)
                    ->schema([
                        TextInput::make('text')
                            ->label(__('Question'))
                            ->required()
                            ->maxLength(500),
                        Repeater::make('options')
                            ->label(__('Options'))
                            ->relationship('options')
                            ->addActionLabel(__('Add option'))
                            ->reorderable(false)
                            ->schema([
                                TextInput::make('text')
                                    ->label(__('Option text'))
                                    ->required()
                                    ->maxLength(500),
                            ])
                            ->defaultItems(2)
                            ->minItems(2)
                            ->required(),
                    ])
                    ->required()
                    ->defaultItems(1)
                    ->minItems(1)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label(__('Active'))
                    ->default(true),
                Toggle::make('is_anonymous')
                    ->label(__('Anonymous voting'))
                    ->disabled(fn (?Poll $record): bool => $record?->votes()->exists() ?? false)
                    ->helperText(__('If enabled, the poll keeps voters private while still preventing duplicate votes.'))
                    ->default(true),
                DateTimePicker::make('ends_at')
                    ->label(__('Ends at')),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('building'))
            ->columns([
                TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('building.name')
                    ->label(__('Building'))
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('participation')
                    ->label(__('Participation'))
                    ->state(fn (Poll $record): string => $record->participationLabel()),
                TextColumn::make('outcome')
                    ->label(__('Outcome'))
                    ->state(fn (Poll $record): string => $record->outcomeLabel())
                    ->badge()
                    ->color(fn (Poll $record): string => match ($record->outcome()) {
                        true => 'success',
                        false => 'danger',
                        null => 'gray',
                    }),
                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_anonymous')
                    ->label(__('Anonymous'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label(__('Ends at'))
                    ->placeholder('-')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                static::exportPdfAction(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function exportPdfAction(): Action
    {
        return Action::make('exportPdf')
            ->label(__('Download PDF'))
            ->icon('heroicon-o-arrow-down-tray')
            ->authorize('exportResults')
            ->visible(fn (Poll $record): bool => ! $record->isOpenForVoting())
            ->action(fn (Poll $record) => app(PollReportPdfService::class)->download($record));
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
                ->placeholder('-'),
            IconEntry::make('is_active')
                ->label(__('Active'))
                ->boolean(),
            IconEntry::make('is_anonymous')
                ->label(__('Anonymous'))
                ->boolean(),
            TextEntry::make('participation')
                ->label(__('Participation'))
                ->state(fn (Poll $record): string => $record->participationLabel()),
            TextEntry::make('majority_type')
                ->label(__('Required majority'))
                ->visible(fn (Poll $record): bool => $record->majority_type !== null)
                ->formatStateUsing(fn (string $state): string => $state === 'two_thirds'
                    ? __('Two-thirds majority (at least 2/3)')
                    : __('Simple majority (more than 50%)')),
            TextEntry::make('outcome')
                ->label(__('Outcome'))
                ->visible(fn (Poll $record): bool => $record->outcome() !== null)
                ->state(fn (Poll $record): string => $record->outcomeLabel()),
            TextEntry::make('ends_at')
                ->label(__('Ends at'))
                ->dateTime(),
            RepeatableEntry::make('results')
                ->label(__('Results'))
                ->visible(fn (Poll $record): bool => ! $record->isOpenForVoting())
                ->state(fn (Poll $record): array => $record->results())
                ->schema([
                    TextEntry::make('question')
                        ->label(__('Question')),
                    TextEntry::make('successful')
                        ->label(__('Outcome'))
                        ->visible(fn (Poll $record): bool => $record->majority_type !== null)
                        ->formatStateUsing(fn (bool $state): string => $state ? __('Successful') : __('Unsuccessful')),
                    RepeatableEntry::make('options')
                        ->label(__('Options'))
                        ->schema([
                            TextEntry::make('option')->label(__('Option')),
                            TextEntry::make('votes')->label(__('Votes')),
                        ])
                        ->columns(2),
                ]),
            RepeatableEntry::make('vote_breakdown')
                ->label(__('Who voted'))
                ->visible(fn (Poll $record): bool => ! $record->is_anonymous && ! $record->isOpenForVoting())
                ->state(fn (Poll $record): array => $record->votes()
                    ->with(['user:id,name,email', 'option:id,text', 'answers.question', 'answers.option'])
                    ->latest('created_at')
                    ->get()
                    ->map(fn ($vote): array => [
                        'voter' => $vote->user?->name ?? __('Unknown user'),
                        'email' => $vote->user?->email ?? '-',
                        'option' => $vote->answers->isEmpty()
                            ? ($vote->option?->text ?? '-')
                            : $vote->answers->map(fn ($answer): string => $answer->question->text.': '.$answer->option->text)->implode('; '),
                        'voted_at' => $vote->created_at?->translatedFormat('d M Y, H:i') ?? '-',
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
