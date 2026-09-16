<?php

declare(strict_types=1);

namespace App\Filament\Resources\Finances;

use App\Enums\IncomeCategory;
use App\Enums\PaymentMethod;
use App\Filament\Concerns\TranslatesFilamentLabels;
use App\Filament\Resources\Finances\IncomeResource\Pages\CreateIncome;
use App\Filament\Resources\Finances\IncomeResource\Pages\EditIncome;
use App\Filament\Resources\Finances\IncomeResource\Pages\ListIncomes;
use App\Filament\Resources\Finances\IncomeResource\Pages\ViewIncome;
use App\Models\Building;
use App\Models\BuildingIncome;
use App\Rules\BuildingAcceptsWrites;
use App\Support\Finance\Money;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class IncomeResource extends Resource
{
    use TranslatesFilamentLabels;

    protected static ?string $model = BuildingIncome::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowTrendingUp;

    protected static string|UnitEnum|null $navigationGroup = 'Finansije';

    protected static ?string $navigationLabel = 'Income';

    protected static ?string $modelLabel = 'Income';

    protected static ?string $pluralModelLabel = 'Income';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'description';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Income'))->schema([
                Select::make('building_id')
                    ->label(__('Building'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->rules([new BuildingAcceptsWrites])
                    ->options(fn (): array => Building::writableSelectOptions(Auth::user())),
                DatePicker::make('date')
                    ->label(__('Date'))
                    ->required()
                    ->native(false)
                    ->default(now()),
                TextInput::make('amount')
                    ->label(__('Amount (RSD)'))
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->step('0.01')
                    ->rule('gt:0'),
                Select::make('category')
                    ->label(__('Category'))
                    ->required()
                    ->options(IncomeCategory::options()),
                Select::make('payment_method')
                    ->label(__('Payment method'))
                    ->options(PaymentMethod::options()),
                TextInput::make('description')
                    ->label(__('Description'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('note')
                    ->label(__('Note'))
                    ->rows(3)
                    ->maxLength(2000)
                    ->columnSpanFull(),
                FileUpload::make('documents_uploads')
                    ->label(__('Documents'))
                    ->helperText(__('Upload PDF, image or DOC/DOCX files (max 20 MB each, up to 10 files).'))
                    ->multiple()
                    ->maxFiles(10)
                    ->maxSize(20480)
                    ->acceptedFileTypes([
                        'application/pdf',
                        'image/jpeg',
                        'image/png',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ])
                    ->storeFiles(false)
                    ->dehydrated()
                    ->columnSpanFull(),
                CheckboxList::make('remove_documents')
                    ->label(__('Remove existing documents'))
                    ->options(fn (?BuildingIncome $record): array => $record
                        ? $record->documents()->pluck('original_name', 'id')->all()
                        : [])
                    ->visible(fn (?BuildingIncome $record): bool => $record !== null && $record->documents()->exists())
                    ->dehydrated()
                    ->bulkToggleable()
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('building.name')->label(__('Building')),
            TextEntry::make('date')->label(__('Date'))->date(),
            TextEntry::make('amount')
                ->label(__('Amount'))
                ->formatStateUsing(fn ($state): string => Money::format($state)),
            TextEntry::make('category')
                ->label(__('Category'))
                ->badge()
                ->formatStateUsing(fn ($state): string => $state instanceof IncomeCategory ? $state->label() : (string) $state)
                ->color(fn ($state): string => $state instanceof IncomeCategory ? $state->color() : 'gray'),
            TextEntry::make('payment_method')
                ->label(__('Payment method'))
                ->formatStateUsing(fn ($state): string => $state instanceof PaymentMethod ? $state->label() : '-')
                ->placeholder('-'),
            TextEntry::make('description')->label(__('Description')),
            TextEntry::make('note')->label(__('Note'))->placeholder('-')->columnSpanFull(),
            TextEntry::make('creator.name')->label(__('Created by'))->placeholder('-'),
            TextEntry::make('editor.name')->label(__('Updated by'))->placeholder('-'),
            RepeatableEntry::make('documents')
                ->label(__('Documents'))
                ->visible(fn (BuildingIncome $record): bool => $record->documents()->exists())
                ->schema([
                    TextEntry::make('original_name')
                        ->label(__('File'))
                        ->url(fn ($record): string => route('finances.documents.download', $record))
                        ->openUrlInNewTab(),
                    TextEntry::make('size')
                        ->label(__('Size'))
                        ->formatStateUsing(fn (?int $state): string => $state ? round($state / 1024, 1).' KB' : '-'),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('building'))
            ->columns([
                TextColumn::make('date')
                    ->label(__('Date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('building.name')
                    ->label(__('Building'))
                    ->sortable(),
                TextColumn::make('description')
                    ->label(__('Description'))
                    ->searchable()
                    ->limit(40),
                TextColumn::make('category')
                    ->label(__('Category'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof IncomeCategory ? $state->label() : (string) $state)
                    ->color(fn ($state): string => $state instanceof IncomeCategory ? $state->color() : 'gray'),
                TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->formatStateUsing(fn ($state): string => Money::format($state))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('building_id')
                    ->label(__('Building'))
                    ->options(fn (): array => static::accessibleBuildingOptions()),
                SelectFilter::make('category')
                    ->label(__('Category'))
                    ->options(IncomeCategory::options()),
                Filter::make('date')
                    ->schema([
                        DatePicker::make('from')->label(__('From')),
                        DatePicker::make('until')->label(__('Until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('date', '<=', $date))),
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

        return $query->whereIn('building_id', $user->managedBuildingIds());
    }

    /**
     * @return array<int, string>
     */
    public static function accessibleBuildingOptions(): array
    {
        $user = Auth::user();
        $query = Building::query()->orderBy('name');

        if ($user !== null && ! $user->is_super_admin) {
            $query->whereIn('id', $user->managedBuildingIds());
        }

        return $query->pluck('name', 'id')->all();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIncomes::route('/'),
            'create' => CreateIncome::route('/create'),
            'view' => ViewIncome::route('/{record}'),
            'edit' => EditIncome::route('/{record}/edit'),
        ];
    }
}
