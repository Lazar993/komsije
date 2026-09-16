<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Building;
use App\Models\User;
use App\Services\Finance\FinancialReportPdfService;
use App\Services\FinancialSummaryService;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class FinancialOverview extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Finansije';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'finances/overview';

    protected string $view = 'filament.pages.financial-overview';

    public string $period = 'this_month';

    public string $building = 'all';

    public ?string $customFrom = null;

    public ?string $customUntil = null;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Finansije');
    }

    public static function getNavigationLabel(): string
    {
        return __('Financial overview');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Financial overview');
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label(__('Export PDF'))
                ->icon('heroicon-o-document-arrow-down')
                ->modalHeading(__('Export financial report (PDF)'))
                ->modalSubmitActionLabel(__('Export PDF'))
                ->schema([
                    Select::make('building_id')
                        ->label(__('Building'))
                        ->required()
                        ->options(fn (): array => $this->reportBuildingOptions())
                        ->default(fn (): ?int => $this->defaultReportBuildingId())
                        ->disabled(fn (): bool => ! $this->hasMultipleBuildings())
                        ->dehydrated(),
                    DatePicker::make('from')
                        ->label(__('From'))
                        ->required()
                        ->native(false)
                        ->default(fn (): ?string => $this->defaultReportRange()[0]),
                    DatePicker::make('until')
                        ->label(__('Until'))
                        ->required()
                        ->native(false)
                        ->default(fn (): ?string => $this->defaultReportRange()[1])
                        ->rules([
                            fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get): void {
                                $from = $get('from');

                                if ($from !== null && $value !== null && Carbon::parse($value)->lt(Carbon::parse($from))) {
                                    $fail(__('The end date must be the same as or after the start date.'));
                                }
                            },
                        ]),
                ])
                ->action(fn (array $data): ?StreamedResponse => $this->exportReport($data)),
        ];
    }

    private function exportReport(array $data): ?StreamedResponse
    {
        $user = Filament::auth()->user();
        $buildingId = (int) ($data['building_id'] ?? 0);

        if (! $user instanceof User || ! $user->isBuildingAdmin($buildingId)) {
            Notification::make()
                ->title(__('You are not authorized to export this building.'))
                ->danger()
                ->send();

            return null;
        }

        $building = Building::query()->find($buildingId);

        if ($building === null) {
            Notification::make()
                ->title(__('The selected building could not be found.'))
                ->danger()
                ->send();

            return null;
        }

        try {
            $from = Carbon::parse($data['from'])->startOfDay();
            $until = Carbon::parse($data['until'])->endOfDay();

            return app(FinancialReportPdfService::class)->download($building, $from, $until);
        } catch (\Throwable $exception) {
            Log::error('Financial report PDF generation failed', [
                'building_id' => $buildingId,
                'exception' => $exception,
            ]);

            Notification::make()
                ->title(__('The financial report could not be generated.'))
                ->danger()
                ->send();

            return null;
        }
    }

    /**
     * Managed buildings available for the report (never includes the "all" option).
     *
     * @return array<int, string>
     */
    public function reportBuildingOptions(): array
    {
        return Building::query()
            ->whereIn('id', $this->managedBuildingIds())
            ->orderBy('name')
            ->pluck('name', 'id')
            ->map(fn ($name): string => (string) $name)
            ->toArray();
    }

    private function defaultReportBuildingId(): ?int
    {
        if ($this->building !== 'all') {
            return (int) $this->building;
        }

        $managed = $this->managedBuildingIds();

        return count($managed) === 1 ? $managed[0] : null;
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function defaultReportRange(): array
    {
        [$from, $until] = $this->resolveRange();

        $from ??= Carbon::now()->startOfYear();
        $until ??= Carbon::now();

        return [$from->format('Y-m-d'), $until->format('Y-m-d')];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->isBuildingAdmin();
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->isBuildingAdmin();
    }

    /**
     * @return array<string, string>
     */
    public function getPeriodOptions(): array
    {
        return [
            'this_month' => __('Current month'),
            'last_month' => __('Previous month'),
            'this_year' => __('Current year'),
            'last_year' => __('Previous year'),
            'custom' => __('Custom date range'),
            'all' => __('All time'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getBuildingOptions(): array
    {
        $buildings = Building::query()
            ->whereIn('id', $this->managedBuildingIds())
            ->orderBy('name')
            ->pluck('name', 'id')
            ->map(fn ($name): string => (string) $name)
            ->toArray();

        return ['all' => __('All buildings')] + $buildings;
    }

    public function hasMultipleBuildings(): bool
    {
        return count($this->managedBuildingIds()) > 1;
    }

    /**
     * @return array{income: string, expense: string, balance: string}
     */
    public function getTotals(): array
    {
        [$from, $until] = $this->resolveRange();

        return $this->summaryService()->totals($this->scopedBuildingIds(), $from, $until);
    }

    /**
     * @return array<string, string>
     */
    public function getIncomeByCategory(): array
    {
        [$from, $until] = $this->resolveRange();

        return $this->summaryService()->incomeByCategory($this->scopedBuildingIds(), $from, $until);
    }

    /**
     * @return array<string, string>
     */
    public function getExpenseByCategory(): array
    {
        [$from, $until] = $this->resolveRange();

        return $this->summaryService()->expenseByCategory($this->scopedBuildingIds(), $from, $until);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getLedger(): array
    {
        [$from, $until] = $this->resolveRange();

        return $this->summaryService()->ledger($this->scopedBuildingIds(), $from, $until);
    }

    public function getRangeLabel(): string
    {
        [$from, $until] = $this->resolveRange();

        if ($from === null && $until === null) {
            return __('All time');
        }

        $fromLabel = $from?->translatedFormat('d.m.Y.') ?? '…';
        $untilLabel = $until?->translatedFormat('d.m.Y.') ?? '…';

        return $fromLabel.' – '.$untilLabel;
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function resolveRange(): array
    {
        $now = Carbon::now();

        return match ($this->period) {
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'last_year' => [
                $now->copy()->subYear()->startOfYear(),
                $now->copy()->subYear()->endOfYear(),
            ],
            'custom' => [
                $this->customFrom !== null && $this->customFrom !== '' ? Carbon::parse($this->customFrom)->startOfDay() : null,
                $this->customUntil !== null && $this->customUntil !== '' ? Carbon::parse($this->customUntil)->endOfDay() : null,
            ],
            default => [null, null],
        };
    }

    /**
     * @return list<int>
     */
    private function managedBuildingIds(): array
    {
        $user = Filament::auth()->user();

        return $user instanceof User ? $user->managedBuildingIds() : [];
    }

    /**
     * The managed building ids narrowed by the selected building filter.
     *
     * @return list<int>
     */
    private function scopedBuildingIds(): array
    {
        $managed = $this->managedBuildingIds();

        if ($this->building === 'all') {
            return $managed;
        }

        $selected = (int) $this->building;

        return in_array($selected, $managed, true) ? [$selected] : [];
    }

    private function summaryService(): FinancialSummaryService
    {
        return app(FinancialSummaryService::class);
    }
}
