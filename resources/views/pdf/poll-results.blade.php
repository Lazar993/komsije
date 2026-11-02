<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('Poll results report') }} - {{ $poll->title }}</title>
    <style>
        @page { margin: 40px 40px 62px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 11px; line-height: 1.5; color: #17212b; margin: 0; overflow-wrap: break-word; }
        h1 { font-size: 20px; margin: 8px 0 12px; }
        h2 { font-size: 14px; margin: 20px 0 8px; page-break-after: avoid; }
        h3 { font-size: 12px; margin: 16px 0 8px; page-break-after: avoid; }
        p { margin: 6px 0; }
        .header { border-bottom: 2px solid #17212b; padding-bottom: 12px; }
        .muted, .footer { color: #596570; font-size: 10px; }
        .description { white-space: pre-wrap; }
        .decision { border-left: 3px solid #526579; padding-left: 12px; margin: 16px 0; }
        .success { color: #166534; }
        .failure { color: #9f1239; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 8px 0; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th, td { padding: 7px 8px; border-bottom: 1px solid #dce1e6; text-align: left; vertical-align: top; overflow-wrap: break-word; }
        th { background: #f0f3f5; }
        .votes { width: 80px; text-align: right; }
        .footer { position: fixed; bottom: -38px; left: 0; right: 0; text-align: center; }
        .page-number:after { content: counter(page); }
    </style>
</head>
<body>
    <div class="footer">{{ __('Poll results report') }} #{{ $poll->id }} | {{ __('Page') }} <span class="page-number"></span></div>

    <div class="header">
        <div class="muted">{{ __('Poll results report') }} #{{ $poll->id }}</div>
        <h1>{{ $poll->title }}</h1>
        <p><strong>{{ __('Building') }}:</strong> {{ $poll->building?->name ?? '-' }}</p>
        @if ($poll->building?->address)
            <p><strong>{{ __('Address') }}:</strong> {{ $poll->building->address }}</p>
        @endif
        <p><strong>{{ __('Report generated at') }}:</strong> {{ $generatedAt->format('d.m.Y. H:i') }}</p>
        @if ($poll->ends_at)
            <p><strong>{{ __('Ends at') }}:</strong> {{ $poll->ends_at->format('d.m.Y. H:i') }}</p>
        @endif
    </div>

    <h2>{{ __('Description') }}</h2>
    <p class="description">{{ $poll->description ?: '-' }}</p>
    <p><strong>{{ __('Poll type') }}:</strong> {{ $poll->majority_type === null ? __('Informational poll') : __('Decision-making poll') }}</p>
    <p><strong>{{ __('Participation') }}:</strong> {{ __(':voted/:total residents voted', ['voted' => $votedCount, 'total' => $eligibleCount]) }}</p>
    @if ($poll->majority_type !== null)
        <p><strong>{{ __('Required majority') }}:</strong> {{ $poll->majority_type === 'two_thirds' ? __('Two-thirds majority (at least 2/3)') : __('Simple majority (more than 50%)') }}</p>
        <p>{{ __('Required votes per question: :count of :total eligible residents.', ['count' => $requiredVotes, 'total' => $eligibleCount]) }}</p>
    @endif

    <h2>{{ __('Final decision') }}</h2>
    <div class="decision">
        @if ($outcome === null)
            <p>{{ __('This is an informational poll; no decision is required.') }}</p>
        @else
            <p class="{{ $outcome ? 'success' : 'failure' }}"><strong>{{ $outcome ? __('Successful') : __('Unsuccessful') }}</strong></p>
            <p>{{ $outcome ? __('A decision was reached for every question.') : __('The required majority was not reached for every question.') }}</p>
        @endif
    </div>

    <h2>{{ __('Questions and results') }}</h2>
    @foreach ($results as $result)
        <h3>{{ $loop->iteration }}. {{ $result['question'] }}</h3>
        <table>
            <thead>
                <tr>
                    <th>{{ __('Option') }}</th>
                    <th class="votes">{{ __('Votes') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($result['options'] as $option)
                    <tr>
                        <td>{{ $option['option'] }}</td>
                        <td class="votes">{{ $option['votes'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if ($poll->majority_type !== null)
            <p><strong>{{ __('Final decision') }}:</strong>
                @if ($result['decision'] !== null)
                    {{ __('Selected option') }}: {{ $result['decision'] }}
                @else
                    {{ __('No option received the required majority.') }}
                @endif
            </p>
        @endif
    @endforeach
</body>
</html>