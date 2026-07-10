<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\PollCreated;
use App\Models\Building;
use App\Models\Poll;
use Illuminate\Support\Facades\DB;

/**
 * Generates the end-of-trial satisfaction survey.
 *
 * The existing Poll model represents a single question with a set of options,
 * so the survey is expressed as one satisfaction poll with a 1–5 star scale.
 * The poll is dispatched through the existing PollCreated event so managers are
 * notified via the normal notification pipeline and can publish/share it.
 */
final class EndOfTrialSurveyService
{
    public function generate(Building $building): ?Poll
    {
        // Never create more than one satisfaction survey per building.
        $existing = Poll::query()
            ->where('building_id', $building->getKey())
            ->where('title', $this->title())
            ->first();

        if ($existing !== null) {
            return null;
        }

        $poll = DB::transaction(function () use ($building): Poll {
            $poll = Poll::query()->create([
                'building_id' => $building->getKey(),
                'title' => $this->title(),
                'description' => __('Your feedback helps your building decide whether to continue with Komšije. It only takes a moment.'),
                'is_anonymous' => true,
                'is_active' => true,
                'ends_at' => now()->addDays(7),
            ]);

            foreach ($this->options() as $text) {
                $poll->options()->create(['text' => $text]);
            }

            return $poll;
        });

        event(new PollCreated($poll->load('building')));

        return $poll;
    }

    private function title(): string
    {
        return __('How satisfied are you with Komšije?');
    }

    /**
     * @return array<int, string>
     */
    private function options(): array
    {
        return [
            '⭐ ' . __('Very dissatisfied'),
            '⭐⭐ ' . __('Dissatisfied'),
            '⭐⭐⭐ ' . __('Neutral'),
            '⭐⭐⭐⭐ ' . __('Satisfied'),
            '⭐⭐⭐⭐⭐ ' . __('Very satisfied'),
        ];
    }
}
