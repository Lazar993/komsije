<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Enums\ExpenseCategory;
use App\Enums\IncomeCategory;
use App\Models\Building;
use App\Models\BuildingExpense;
use App\Models\BuildingIncome;
use App\Services\FinancialSummaryService;
use Illuminate\Support\Carbon;

/**
 * Assembles all data for a single-building financial report over a date range.
 *
 * Totals, per-category summaries and the running-balance ledger are delegated
 * to {@see FinancialSummaryService} so the report shares the exact same source
 * of truth as the on-screen financial overview. Every query is scoped to the
 * one building passed in; callers are responsible for authorization.
 */
final class FinancialReportBuilder
{
    public function __construct(private readonly FinancialSummaryService $summary) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Building $building, Carbon $from, Carbon $until): array
    {
        $buildingId = (int) $building->getKey();
        $scope = [$buildingId];

        $incomes = $this->incomeRows($buildingId, $from, $until);
        $expenses = $this->expenseRows($buildingId, $from, $until);

        return [
            'building' => [
                'name' => (string) $building->name,
                'address' => (string) ($building->address ?? ''),
            ],
            'from' => $from,
            'until' => $until,
            'generatedAt' => Carbon::now(),
            'totals' => $this->summary->totals($scope, $from, $until),
            'incomes' => $incomes,
            'expenses' => $expenses,
            'incomeByCategory' => $this->summary->incomeByCategory($scope, $from, $until),
            'expenseByCategory' => $this->summary->expenseByCategory($scope, $from, $until),
            'ledger' => $this->summary->chronologicalLedger($scope, $from, $until),
            'hasTransactions' => $incomes !== [] || $expenses !== [],
        ];
    }

    /**
     * @return list<array{date: Carbon, category: string, description: string, amount: string}>
     */
    private function incomeRows(int $buildingId, Carbon $from, Carbon $until): array
    {
        return BuildingIncome::query()
            ->where('building_id', $buildingId)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $until)
            ->orderBy('date')
            ->orderBy('id')
            ->get(['id', 'date', 'category', 'description', 'amount'])
            ->map(fn (BuildingIncome $income): array => [
                'date' => $income->date,
                'category' => $income->category instanceof IncomeCategory ? $income->category->label() : (string) $income->category,
                'description' => (string) $income->description,
                'amount' => $this->normalize((string) $income->amount),
            ])
            ->all();
    }

    /**
     * @return list<array{date: Carbon, category: string, description: string, vendor: ?string, invoice: ?string, ticket: ?array{id: int, title: string}, amount: string}>
     */
    private function expenseRows(int $buildingId, Carbon $from, Carbon $until): array
    {
        return BuildingExpense::query()
            ->with(['vendor:id,name', 'ticket:id,title'])
            ->where('building_id', $buildingId)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $until)
            ->orderBy('date')
            ->orderBy('id')
            ->get(['id', 'date', 'category', 'description', 'vendor_id', 'ticket_id', 'invoice_number', 'amount'])
            ->map(fn (BuildingExpense $expense): array => [
                'date' => $expense->date,
                'category' => $expense->category instanceof ExpenseCategory ? $expense->category->label() : (string) $expense->category,
                'description' => (string) $expense->description,
                'vendor' => $expense->vendor?->name,
                'invoice' => $expense->invoice_number !== null && $expense->invoice_number !== '' ? (string) $expense->invoice_number : null,
                'ticket' => $expense->ticket !== null
                    ? ['id' => (int) $expense->ticket->getKey(), 'title' => (string) $expense->ticket->title]
                    : null,
                'amount' => $this->normalize((string) $expense->amount),
            ])
            ->all();
    }

    private function normalize(string $amount): string
    {
        return bcadd($amount === '' ? '0' : $amount, '0', 2);
    }
}
