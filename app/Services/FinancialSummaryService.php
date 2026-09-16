<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ExpenseCategory;
use App\Enums\IncomeCategory;
use App\Models\BuildingExpense;
use App\Models\BuildingIncome;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Read-only financial aggregation for the manager overview. All queries are
 * scoped to an explicit list of building ids so a caller can never accidentally
 * aggregate across buildings the manager does not control. Totals are computed
 * with database SUM aggregation, never by loading rows into PHP.
 */
final class FinancialSummaryService
{
    /**
     * @param  list<int>  $buildingIds
     * @return array{income: string, expense: string, balance: string}
     */
    public function totals(array $buildingIds, ?Carbon $from = null, ?Carbon $until = null): array
    {
        if ($buildingIds === []) {
            return ['income' => '0.00', 'expense' => '0.00', 'balance' => '0.00'];
        }

        $income = (string) BuildingIncome::query()
            ->whereIn('building_id', $buildingIds)
            ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
            ->when($until, fn ($q) => $q->whereDate('date', '<=', $until))
            ->sum('amount');

        $expense = (string) BuildingExpense::query()
            ->whereIn('building_id', $buildingIds)
            ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
            ->when($until, fn ($q) => $q->whereDate('date', '<=', $until))
            ->sum('amount');

        $balance = bcsub($this->normalize($income), $this->normalize($expense), 2);

        return [
            'income' => $this->normalize($income),
            'expense' => $this->normalize($expense),
            'balance' => $balance,
        ];
    }

    /**
     * @param  list<int>  $buildingIds
     * @return array<string, string> category label => total
     */
    public function incomeByCategory(array $buildingIds, ?Carbon $from = null, ?Carbon $until = null): array
    {
        if ($buildingIds === []) {
            return [];
        }

        return BuildingIncome::query()
            ->whereIn('building_id', $buildingIds)
            ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
            ->when($until, fn ($q) => $q->whereDate('date', '<=', $until))
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->mapWithKeys(fn ($total, $category): array => [
                (IncomeCategory::tryFrom((string) $category)?->label() ?? (string) $category) => $this->normalize((string) $total),
            ])
            ->all();
    }

    /**
     * @param  list<int>  $buildingIds
     * @return array<string, string> category label => total
     */
    public function expenseByCategory(array $buildingIds, ?Carbon $from = null, ?Carbon $until = null): array
    {
        if ($buildingIds === []) {
            return [];
        }

        return BuildingExpense::query()
            ->whereIn('building_id', $buildingIds)
            ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
            ->when($until, fn ($q) => $q->whereDate('date', '<=', $until))
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->mapWithKeys(fn ($total, $category): array => [
                (ExpenseCategory::tryFrom((string) $category)?->label() ?? (string) $category) => $this->normalize((string) $total),
            ])
            ->all();
    }

    /**
     * Chronological ledger with a deterministic running balance. Rows are
     * ordered by date, then income before expense on the same day, then id, so
     * the running balance is stable for same-day transactions.
     *
     * @param  list<int>  $buildingIds
     * @return list<array{date: Carbon, type: string, description: string, category: string, income: ?string, expense: ?string, balance: string}>
     */
    public function ledger(array $buildingIds, ?Carbon $from = null, ?Carbon $until = null, int $limit = 500): array
    {
        // Show the most recent transactions first, keeping the computed balance.
        return array_slice(array_reverse($this->computeLedger($buildingIds, $from, $until)), 0, $limit);
    }

    /**
     * Same deterministic running balance as {@see ledger()} but returned in
     * ascending chronological order and without a display limit, suitable for a
     * printable report.
     *
     * @param  list<int>  $buildingIds
     * @return list<array{date: Carbon, type: string, description: string, category: string, income: ?string, expense: ?string, balance: string}>
     */
    public function chronologicalLedger(array $buildingIds, ?Carbon $from = null, ?Carbon $until = null): array
    {
        return $this->computeLedger($buildingIds, $from, $until);
    }

    /**
     * @param  list<int>  $buildingIds
     * @return list<array{date: Carbon, type: string, description: string, category: string, income: ?string, expense: ?string, balance: string}>
     */
    private function computeLedger(array $buildingIds, ?Carbon $from = null, ?Carbon $until = null): array
    {
        if ($buildingIds === []) {
            return [];
        }

        $incomes = BuildingIncome::query()
            ->whereIn('building_id', $buildingIds)
            ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
            ->when($until, fn ($q) => $q->whereDate('date', '<=', $until))
            ->get(['id', 'date', 'description', 'category', 'amount'])
            ->map(fn (BuildingIncome $income): array => [
                'sort_date' => $income->date,
                'type_order' => 0,
                'id' => (int) $income->getKey(),
                'type' => 'income',
                'description' => (string) $income->description,
                'category' => $income->category instanceof IncomeCategory ? $income->category->label() : (string) $income->category,
                'income' => $this->normalize((string) $income->amount),
                'expense' => null,
            ]);

        $expenses = BuildingExpense::query()
            ->whereIn('building_id', $buildingIds)
            ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
            ->when($until, fn ($q) => $q->whereDate('date', '<=', $until))
            ->get(['id', 'date', 'description', 'category', 'amount'])
            ->map(fn (BuildingExpense $expense): array => [
                'sort_date' => $expense->date,
                'type_order' => 1,
                'id' => (int) $expense->getKey(),
                'type' => 'expense',
                'description' => (string) $expense->description,
                'category' => $expense->category instanceof ExpenseCategory ? $expense->category->label() : (string) $expense->category,
                'income' => null,
                'expense' => $this->normalize((string) $expense->amount),
            ]);

        /** @var Collection<int, array<string, mixed>> $rows */
        $rows = $incomes->concat($expenses)
            ->sortBy([
                ['sort_date', 'asc'],
                ['type_order', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        $running = '0.00';
        $ledger = [];

        foreach ($rows as $row) {
            $running = $row['income'] !== null
                ? bcadd($running, $row['income'], 2)
                : bcsub($running, $row['expense'], 2);

            $ledger[] = [
                'date' => $row['sort_date'],
                'type' => $row['type'],
                'description' => $row['description'],
                'category' => $row['category'],
                'income' => $row['income'],
                'expense' => $row['expense'],
                'balance' => $running,
            ];
        }

        return $ledger;
    }

    private function normalize(string $amount): string
    {
        return bcadd($amount === '' ? '0' : $amount, '0', 2);
    }
}
