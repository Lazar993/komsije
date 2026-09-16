<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>Finansijski izveštaj</title>
    @php
        $money = fn ($v) => \App\Support\Finance\Money::format($v);
        $balanceNegative = bccomp($totals['balance'], '0', 2) < 0;
    @endphp
    <style>
        * { box-sizing: border-box; }
        @page { margin: 40px 40px 64px 40px; }
        body { font-family: "DejaVu Sans", sans-serif; margin: 0; color: #0f172a; font-size: 11px; line-height: 1.45; }

        .page-footer { position: fixed; bottom: -44px; left: 0; right: 0; text-align: center; font-size: 9px; color: #64748b; }
        .page-footer .num:after { content: counter(page); }

        .header { border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 16px; }
        .brand { font-size: 20px; font-weight: 700; letter-spacing: 0.5px; }
        .doc-title { font-size: 15px; font-weight: 700; margin-top: 2px; color: #1e293b; }
        .meta { margin-top: 8px; font-size: 10.5px; color: #334155; }
        .meta strong { color: #0f172a; }

        h2.section { font-size: 12.5px; font-weight: 700; margin: 20px 0 8px; padding-bottom: 4px; border-bottom: 1px solid #cbd5e1; color: #1e293b; }

        .summary { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .summary td { width: 33.33%; border: 1px solid #cbd5e1; padding: 10px 12px; vertical-align: top; }
        .summary .label { font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.4px; color: #64748b; }
        .summary .value { font-size: 15px; font-weight: 700; margin-top: 4px; }
        .value.income { color: #15803d; }
        .value.expense { color: #b91c1c; }
        .value.balance-pos { color: #0f172a; }
        .value.balance-neg { color: #b91c1c; }

        table.data { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.data th { background: #f1f5f9; text-align: left; font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.3px; color: #475569; padding: 6px 8px; border-bottom: 1px solid #cbd5e1; }
        table.data td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        table.data td.num, table.data th.num { text-align: right; white-space: nowrap; }
        table.data tr.total td { font-weight: 700; border-top: 2px solid #cbd5e1; border-bottom: none; background: #f8fafc; }
        .amount-income { color: #15803d; }
        .amount-expense { color: #b91c1c; }
        .muted { color: #94a3b8; }
        .empty-note { padding: 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #475569; font-size: 11px; }
        .subref { color: #64748b; font-size: 9.5px; }
    </style>
</head>
<body>
    <div class="page-footer">
        Komšije — Finansijski izveštaj &nbsp;·&nbsp; Strana <span class="num"></span>
    </div>

    <div class="header">
        <div class="brand">KOMŠIJE</div>
        <div class="doc-title">FINANSIJSKI IZVEŠTAJ</div>
        <div class="meta">
            <div><strong>Zgrada:</strong> {{ $building['name'] }}@if ($building['address'] !== ''), {{ $building['address'] }}@endif</div>
            <div><strong>Period:</strong> {{ $from->format('d.m.Y.') }} – {{ $until->format('d.m.Y.') }}</div>
            <div><strong>Generisano:</strong> {{ $generatedAt->format('d.m.Y. H:i') }}</div>
        </div>
    </div>

    {{-- Summary --}}
    <table class="summary">
        <tr>
            <td>
                <div class="label">Ukupni prihodi</div>
                <div class="value income">{{ $money($totals['income']) }}</div>
            </td>
            <td>
                <div class="label">Ukupni rashodi</div>
                <div class="value expense">{{ $money($totals['expense']) }}</div>
            </td>
            <td>
                <div class="label">Evidentirano stanje</div>
                <div class="value {{ $balanceNegative ? 'balance-neg' : 'balance-pos' }}">{{ $money($totals['balance']) }}</div>
            </td>
        </tr>
    </table>

    @if (! $hasTransactions)
        <div class="empty-note" style="margin-top: 18px;">
            U izabranom periodu nije evidentirana nijedna finansijska transakcija.
        </div>
    @else
        {{-- Income --}}
        <h2 class="section">Prihodi</h2>
        @if (count($incomes) > 0)
            <table class="data">
                <thead>
                    <tr>
                        <th style="width: 70px;">Datum</th>
                        <th style="width: 150px;">Kategorija</th>
                        <th>Opis</th>
                        <th class="num" style="width: 120px;">Iznos</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($incomes as $row)
                        <tr>
                            <td>{{ $row['date']->format('d.m.Y.') }}</td>
                            <td>{{ $row['category'] }}</td>
                            <td>{{ $row['description'] }}</td>
                            <td class="num amount-income">{{ $money($row['amount']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total">
                        <td colspan="3">UKUPNO PRIHODI</td>
                        <td class="num amount-income">{{ $money($totals['income']) }}</td>
                    </tr>
                </tbody>
            </table>
        @else
            <div class="empty-note">Nema prihoda u izabranom periodu.</div>
        @endif

        {{-- Expenses --}}
        <h2 class="section">Rashodi</h2>
        @if (count($expenses) > 0)
            <table class="data">
                <thead>
                    <tr>
                        <th style="width: 66px;">Datum</th>
                        <th style="width: 118px;">Kategorija</th>
                        <th>Opis</th>
                        <th style="width: 110px;">Izvođač</th>
                        <th style="width: 70px;">Račun</th>
                        <th class="num" style="width: 100px;">Iznos</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($expenses as $row)
                        <tr>
                            <td>{{ $row['date']->format('d.m.Y.') }}</td>
                            <td>{{ $row['category'] }}</td>
                            <td>
                                {{ $row['description'] }}
                                @if ($row['ticket'] !== null)
                                    <div class="subref">Prijava #{{ $row['ticket']['id'] }} — {{ $row['ticket']['title'] }}</div>
                                @endif
                            </td>
                            <td>{{ $row['vendor'] ?? '—' }}</td>
                            <td>{{ $row['invoice'] ?? '—' }}</td>
                            <td class="num amount-expense">{{ $money($row['amount']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total">
                        <td colspan="5">UKUPNO RASHODI</td>
                        <td class="num amount-expense">{{ $money($totals['expense']) }}</td>
                    </tr>
                </tbody>
            </table>
        @else
            <div class="empty-note">Nema rashoda u izabranom periodu.</div>
        @endif

        {{-- Expense summary by category --}}
        @if (count($expenseByCategory) > 0)
            <h2 class="section">Rashodi po kategoriji</h2>
            <table class="data">
                <thead>
                    <tr>
                        <th>Kategorija</th>
                        <th class="num" style="width: 140px;">Ukupno</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($expenseByCategory as $category => $total)
                        <tr>
                            <td>{{ $category }}</td>
                            <td class="num">{{ $money($total) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total">
                        <td>UKUPNO</td>
                        <td class="num amount-expense">{{ $money($totals['expense']) }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

        {{-- Ledger --}}
        <h2 class="section">Knjiga transakcija</h2>
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 62px;">Datum</th>
                    <th style="width: 54px;">Tip</th>
                    <th>Opis</th>
                    <th style="width: 100px;">Kategorija</th>
                    <th class="num" style="width: 82px;">Prihod</th>
                    <th class="num" style="width: 82px;">Rashod</th>
                    <th class="num" style="width: 90px;">Stanje</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($ledger as $row)
                    <tr>
                        <td>{{ $row['date']->format('d.m.Y.') }}</td>
                        <td>{{ $row['type'] === 'income' ? 'Prihod' : 'Rashod' }}</td>
                        <td>{{ $row['description'] }}</td>
                        <td>{{ $row['category'] }}</td>
                        <td class="num amount-income">{{ $row['income'] !== null ? $money($row['income']) : '' }}</td>
                        <td class="num amount-expense">{{ $row['expense'] !== null ? $money($row['expense']) : '' }}</td>
                        <td class="num">{{ $money($row['balance']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
