<x-filament-panels::page>
    @php
        $totals = $this->getTotals();
        $incomeByCategory = $this->getIncomeByCategory();
        $expenseByCategory = $this->getExpenseByCategory();
        $ledger = $this->getLedger();
        $balanceNegative = bccomp($totals['balance'], '0', 2) < 0;
        $money = fn ($v) => \App\Support\Finance\Money::format($v);
    @endphp

    {{-- Filters --}}
    <x-filament::section icon="heroicon-o-adjustments-horizontal" icon-color="primary">
        <x-slot name="heading">{{ __('Filter by period') }}</x-slot>

        <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; flex-direction: column; gap: 0.375rem; flex: 1 1 12rem; min-width: 12rem;">
                <label for="period" class="text-xs font-medium text-gray-500 dark:text-gray-400">
                    {{ __('Filter by period') }}
                </label>
                <x-filament::input.wrapper>
                    <x-filament::input.select id="period" wire:model.live="period">
                        @foreach ($this->getPeriodOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>

            @if ($this->hasMultipleBuildings())
                <div style="display: flex; flex-direction: column; gap: 0.375rem; flex: 1 1 12rem; min-width: 12rem;">
                    <label for="building" class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        {{ __('Building') }}
                    </label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select id="building" wire:model.live="building">
                            @foreach ($this->getBuildingOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            @endif

            @if ($period === 'custom')
                <div style="display: flex; flex-direction: column; gap: 0.375rem; flex: 1 1 10rem; min-width: 10rem;">
                    <label for="customFrom" class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        {{ __('From') }}
                    </label>
                    <x-filament::input.wrapper>
                        <x-filament::input type="date" id="customFrom" wire:model.live="customFrom" />
                    </x-filament::input.wrapper>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.375rem; flex: 1 1 10rem; min-width: 10rem;">
                    <label for="customUntil" class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        {{ __('Until') }}
                    </label>
                    <x-filament::input.wrapper>
                        <x-filament::input type="date" id="customUntil" wire:model.live="customUntil" />
                    </x-filament::input.wrapper>
                </div>
            @endif
        </div>

        <div style="margin-top: 1rem; display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;" class="text-sm text-gray-500 dark:text-gray-400">
            @svg('heroicon-m-calendar-days', '', ['style' => 'width: 1rem; height: 1rem; flex-shrink: 0;'])
            <span>{{ __('Showing') }}: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $this->getRangeLabel() }}</span></span>
            <span wire:loading style="color: #d97706; font-weight: 500;">
                {{ __('Updating...') }}
            </span>
        </div>
    </x-filament::section>

    {{-- Totals --}}
    <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
        <div style="flex: 1 1 15rem;">
            <x-filament::section>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="display: flex; height: 3rem; width: 3rem; flex-shrink: 0; align-items: center; justify-content: center; border-radius: 0.75rem; background: rgba(22, 163, 74, 0.12); color: #16a34a;">
                        @svg('heroicon-o-arrow-trending-up', '', ['style' => 'width: 1.5rem; height: 1.5rem;'])
                    </div>
                    <div style="min-width: 0;">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Total income') }}</div>
                        <div style="margin-top: 0.125rem; font-size: 1.25rem; line-height: 1.75rem; font-weight: 700; font-variant-numeric: tabular-nums; color: #16a34a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            {{ $money($totals['income']) }}
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <div style="flex: 1 1 15rem;">
            <x-filament::section>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="display: flex; height: 3rem; width: 3rem; flex-shrink: 0; align-items: center; justify-content: center; border-radius: 0.75rem; background: rgba(220, 38, 38, 0.12); color: #dc2626;">
                        @svg('heroicon-o-arrow-trending-down', '', ['style' => 'width: 1.5rem; height: 1.5rem;'])
                    </div>
                    <div style="min-width: 0;">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Total expenses') }}</div>
                        <div style="margin-top: 0.125rem; font-size: 1.25rem; line-height: 1.75rem; font-weight: 700; font-variant-numeric: tabular-nums; color: #dc2626; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            {{ $money($totals['expense']) }}
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <div style="flex: 1 1 15rem;">
            <x-filament::section>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="display: flex; height: 3rem; width: 3rem; flex-shrink: 0; align-items: center; justify-content: center; border-radius: 0.75rem; background: {{ $balanceNegative ? 'rgba(220, 38, 38, 0.12)' : 'rgba(217, 119, 6, 0.14)' }}; color: {{ $balanceNegative ? '#dc2626' : '#d97706' }};">
                        @svg('heroicon-o-banknotes', '', ['style' => 'width: 1.5rem; height: 1.5rem;'])
                    </div>
                    <div style="min-width: 0;">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Recorded balance') }}</div>
                        <div style="margin-top: 0.125rem; font-size: 1.25rem; line-height: 1.75rem; font-weight: 700; font-variant-numeric: tabular-nums; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; {{ $balanceNegative ? 'color: #dc2626;' : '' }}"
                            @class(['text-gray-950 dark:text-white' => ! $balanceNegative])>
                            {{ $money($totals['balance']) }}
                        </div>
                        <p style="margin-top: 0.25rem;" class="text-xs text-gray-400">
                            {{ __('Calculated from recorded income and expenses. Not a bank balance.') }}
                        </p>
                    </div>
                </div>
            </x-filament::section>
        </div>
    </div>

    {{-- Category breakdowns --}}
    <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
        <div style="flex: 1 1 22rem;">
            <x-filament::section icon="heroicon-o-arrow-trending-up" icon-color="success">
                <x-slot name="heading">{{ __('Income by category') }}</x-slot>
                @if (count($incomeByCategory) > 0)
                    <div>
                        @foreach ($incomeByCategory as $category => $total)
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.625rem 0; border-top: 1px solid rgba(128, 128, 128, 0.15);">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $category }}</span>
                                <span style="font-variant-numeric: tabular-nums;" class="text-sm font-semibold text-gray-950 dark:text-white">{{ $money($total) }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem; padding: 1.5rem 0; text-align: center;">
                        @svg('heroicon-o-inbox', '', ['style' => 'width: 2rem; height: 2rem;', 'class' => 'text-gray-300 dark:text-gray-600'])
                        <p class="text-sm text-gray-400">{{ __('No records for this period.') }}</p>
                    </div>
                @endif
            </x-filament::section>
        </div>

        <div style="flex: 1 1 22rem;">
            <x-filament::section icon="heroicon-o-arrow-trending-down" icon-color="danger">
                <x-slot name="heading">{{ __('Expenses by category') }}</x-slot>
                @if (count($expenseByCategory) > 0)
                    <div>
                        @foreach ($expenseByCategory as $category => $total)
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.625rem 0; border-top: 1px solid rgba(128, 128, 128, 0.15);">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $category }}</span>
                                <span style="font-variant-numeric: tabular-nums;" class="text-sm font-semibold text-gray-950 dark:text-white">{{ $money($total) }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem; padding: 1.5rem 0; text-align: center;">
                        @svg('heroicon-o-inbox', '', ['style' => 'width: 2rem; height: 2rem;', 'class' => 'text-gray-300 dark:text-gray-600'])
                        <p class="text-sm text-gray-400">{{ __('No records for this period.') }}</p>
                    </div>
                @endif
            </x-filament::section>
        </div>
    </div>

    {{-- Ledger --}}
    <x-filament::section icon="heroicon-o-table-cells" icon-color="primary">
        <x-slot name="heading">{{ __('Transaction ledger') }}</x-slot>
        <x-slot name="description">
            {{ __('Chronological record with a running recorded balance.') }}
        </x-slot>

        @if (count($ledger) > 0)
            @php
                $headCell = 'padding: 0.625rem 0.75rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em; white-space: nowrap;';
                $bodyCell = 'padding: 0.625rem 0.75rem;';
            @endphp
            <div style="overflow: auto; max-height: 32rem; border: 1px solid rgba(128, 128, 128, 0.2); border-radius: 0.75rem;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                    <thead>
                        <tr style="background: rgba(128, 128, 128, 0.08);" class="text-gray-500 dark:text-gray-400">
                            <th style="{{ $headCell }} text-align: left;">{{ __('Date') }}</th>
                            <th style="{{ $headCell }} text-align: left;">{{ __('Type') }}</th>
                            <th style="{{ $headCell }} text-align: left; width: 100%;">{{ __('Description') }}</th>
                            <th style="{{ $headCell }} text-align: left;">{{ __('Category') }}</th>
                            <th style="{{ $headCell }} text-align: right;">{{ __('Income') }}</th>
                            <th style="{{ $headCell }} text-align: right;">{{ __('Expense') }}</th>
                            <th style="{{ $headCell }} text-align: right;">{{ __('Recorded balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ledger as $row)
                            @php $rowNegative = bccomp($row['balance'], '0', 2) < 0; @endphp
                            <tr style="border-top: 1px solid rgba(128, 128, 128, 0.15);">
                                <td style="{{ $bodyCell }} white-space: nowrap; font-variant-numeric: tabular-nums;" class="text-gray-700 dark:text-gray-300">
                                    {{ $row['date']->translatedFormat('d.m.Y.') }}
                                </td>
                                <td style="{{ $bodyCell }} white-space: nowrap;">
                                    @if ($row['type'] === 'income')
                                        <x-filament::badge color="success">{{ __('Income') }}</x-filament::badge>
                                    @else
                                        <x-filament::badge color="danger">{{ __('Expense') }}</x-filament::badge>
                                    @endif
                                </td>
                                <td style="{{ $bodyCell }} max-width: 20rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" class="text-gray-950 dark:text-white" title="{{ $row['description'] }}">
                                    {{ $row['description'] }}
                                </td>
                                <td style="{{ $bodyCell }} white-space: nowrap;" class="text-gray-500 dark:text-gray-400">
                                    {{ $row['category'] }}
                                </td>
                                <td style="{{ $bodyCell }} white-space: nowrap; text-align: right; font-variant-numeric: tabular-nums; font-weight: 500; color: {{ $row['income'] !== null ? '#16a34a' : '#9ca3af' }};">
                                    {{ $row['income'] !== null ? $money($row['income']) : '—' }}
                                </td>
                                <td style="{{ $bodyCell }} white-space: nowrap; text-align: right; font-variant-numeric: tabular-nums; font-weight: 500; color: {{ $row['expense'] !== null ? '#dc2626' : '#9ca3af' }};">
                                    {{ $row['expense'] !== null ? $money($row['expense']) : '—' }}
                                </td>
                                <td style="{{ $bodyCell }} white-space: nowrap; text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; {{ $rowNegative ? 'color: #dc2626;' : '' }}"
                                    @class(['text-gray-950 dark:text-white' => ! $rowNegative])>
                                    {{ $money($row['balance']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="display: flex; flex-direction: column; align-items: center; gap: 0.75rem; padding: 2.5rem 0; text-align: center;">
                @svg('heroicon-o-document-magnifying-glass', '', ['style' => 'width: 2.5rem; height: 2.5rem;', 'class' => 'text-gray-300 dark:text-gray-600'])
                <p class="text-sm text-gray-400">{{ __('No transactions for this period.') }}</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
