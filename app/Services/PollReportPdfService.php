<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Poll;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PollReportPdfService
{
    public function data(Poll $poll): array
    {
        $poll->refresh();
        Gate::authorize('exportResults', $poll);
        abort_if($poll->isOpenForVoting(), 422, __('The poll report is available only after voting closes.'));

        $poll->loadMissing('building');
        $eligibleCount = $poll->eligibleVoters()->count();
        $requiredVotes = $poll->requiredVotes($eligibleCount);
        $results = $poll->results();

        foreach ($results as &$result) {
            $result['decision'] = $poll->majority_type === null
                ? null
                : collect($result['options'])->first(fn (array $option): bool => $option['votes'] >= $requiredVotes)['option'] ?? null;
        }
        unset($result);

        return [
            'poll' => $poll,
            'results' => $results,
            'eligibleCount' => $eligibleCount,
            'votedCount' => $poll->eligibleVotes()->count(),
            'requiredVotes' => $requiredVotes,
            'outcome' => $poll->majority_type === null ? null : collect($results)->every('successful', true),
            'generatedAt' => now(),
        ];
    }

    public function pdf(Poll $poll): string
    {
        return Pdf::loadView('pdf.poll-results', $this->data($poll))
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', false)
            ->output();
    }

    public function filename(Poll $poll): string
    {
        $slug = Str::slug(Str::limit((string) $poll->title, 100, ''));

        return 'poll-results-'.$poll->getKey().($slug !== '' ? '-'.$slug : '').'.pdf';
    }

    public function download(Poll $poll): StreamedResponse
    {
        $content = $this->pdf($poll);

        return response()->streamDownload(
            static function () use ($content): void {
                echo $content;
            },
            $this->filename($poll),
            [
                'Content-Type' => 'application/pdf',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ],
        );
    }
}
