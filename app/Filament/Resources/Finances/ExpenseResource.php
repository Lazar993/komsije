<?php

declare(strict_types=1);

namespace App\Filament\Resources\Finances;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Filament\Concerns\TranslatesFilamentLabels;
use App\Filament\Resources\Finances\ExpenseResource\Pages\CreateExpense;
use App\Filament\Resources\Finances\ExpenseResource\Pages\EditExpense;
use App\Filament\Resources\Finances\ExpenseResource\Pages\ListExpenses;
use App\Filament\Resources\Finances\ExpenseResource\Pages\ViewExpense;
use App\Models\Building;
use App\Models\BuildingExpense;
use App\Models\Ticket;
use App\Models\Vendor;
use App\Rules\BuildingAcceptsWrites;
use App\Support\Finance\Money;
use BackedEnum;
use Closure;
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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ExpenseResource extends Resource
{
    use TranslatesFilamentLabels;

    protected static ?string $model = BuildingExpense::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowTrendingDown;

    protected static string|UnitEnum|null $navigationGroup = 'Finansije';

    protected static ?string $navigationLabel = 'Expenses';

    protected static ?string $modelLabel = 'Expense';

    protected static ?string $pluralModelLabel = 'Expenses';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'description';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Expense'))->schema([
                Select::make('building_id')
                    ->label(__('Building'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
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
                    ->options(ExpenseCategory::options()),
                Select::make('vendor_id')
                    ->label(__('Vendor / Contractor'))
                    ->searchable()
                    ->options(fn (Get $get): array => Vendor::query()
                        ->when($get('building_id'), fn (Builder $query, $buildingId): Builder => $query->where('building_id', $buildingId))
                        ->active()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get): void {
                            if ($value === null || $value === '') {
                                return;
                            }

                            $belongs = Vendor::query()
                                ->whereKey($value)
                                ->where('building_id', $get('building_id'))
                                ->exists();

                            if (! $belongs) {
                                $fail(__('The selected vendor does not belong to this building.'));
                            }
                        },
                    ]),
                Select::make('ticket_id')
                    ->label(__('Related ticket'))
                    ->searchable()
                    ->helperText(__('Only tickets from the selected building can be linked.'))
                    ->options(fn (Get $get): array => Ticket::query()
                        ->when(
                            $get('building_id'),
                            fn (Builder $query, $buildingId): Builder => $query->where('building_id', $buildingId),
                            fn (Builder $query): Builder => $query->whereRaw('1 = 0'),
                        )
                        ->orderByDesc('created_at')
                        ->pluck('title', 'id')
                        ->all())
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get): void {
                            if ($value === null || $value === '') {
                                return;
                            }

                            $belongs = Ticket::query()
                                ->whereKey($value)
                                ->where('building_id', $get('building_id'))
                                ->exists();

                            if (! $belongs) {
                                $fail(__('The selected ticket does not belong to this building.'));
                            }
                        },
                    ]),
                Select::make('payment_method')
                    ->label(__('Payment method'))
                    ->options(PaymentMethod::options()),
                TextInput::make('invoice_number')
                    ->label(__('Invoice number'))
                    ->maxLength(128),
                TextInput::make('description')
                    ->label(__('Description'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
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
                    ->options(fn (?BuildingExpense $record): array => $record
                        ? $record->documents()->pluck('original_name', 'id')->all()
                        : [])
                    ->visible(fn (?BuildingExpense $record): bool => $record !== null && $record->documents()->exists())
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
                ->formatStateUsing(fn ($state): string => $state instanceof ExpenseCategory ? $state->label() : (string) $state)
                ->color(fn ($state): string => $state instanceof ExpenseCategory ? $state->color() : 'gray'),
            TextEntry::make('vendor.name')->label(__('Vendor / Contractor'))->placeholder('-'),
            TextEntry::make('ticket.title')->label(__('Related ticket'))->placeholder('-'),
            TextEntry::make('invoice_number')->label(__('Invoice number'))->placeholder('-'),
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
                ->visible(fn (BuildingExpense $record): bool => $record->documents()->exists())
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
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['building', 'vendor']))
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
                    ->formatStateUsing(fn ($state): string => $state instanceof ExpenseCategory ? $state->label() : (string) $state)
                    ->color(fn ($state): string => $state instanceof ExpenseCategory ? $state->color() : 'gray'),
                TextColumn::make('vendor.name')
                    ->label(__('Vendor'))
                    ->placeholder('-'),
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
                    ->options(ExpenseCategory::options()),
                SelectFilter::make('vendor_id')
                    ->label(__('Vendor'))
                    ->relationship('vendor', 'name'),
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
            'index' => ListExpenses::route('/'),
            'create' => CreateExpense::route('/create'),
            'view' => ViewExpense::route('/{record}'),
            'edit' => EditExpense::route('/{record}/edit'),
        ];
    }
}
