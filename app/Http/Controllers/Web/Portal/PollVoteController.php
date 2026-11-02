<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Portal;

use App\Http\Requests\Poll\StoreVoteRequest;
use App\Models\Poll;
use App\Models\Vote;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

final class PollVoteController extends PortalController
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function __invoke(StoreVoteRequest $request, Poll $poll): RedirectResponse
    {
        abort_if((int) $poll->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('vote', $poll);
        $userId = (int) $request->user()->getKey();
        abort_unless($poll->eligibleVoters()->whereKey($userId)->exists(), 403);

        try {
            $message = DB::transaction(function () use ($request, $poll, $userId): string {
                $lockedPoll = Poll::query()->lockForUpdate()->findOrFail($poll->id);

                if (! $lockedPoll->isOpenForVoting()) {
                    return __('Voting is closed for this poll.');
                }

                if ($lockedPoll->votes()->where('user_id', $userId)->exists()) {
                    return __('You already voted.');
                }

                $answers = $request->validated('answers', []);
                $vote = $lockedPoll->votes()->create([
                    'poll_option_id' => $answers !== [] ? reset($answers) : $request->validated('poll_option_id'),
                    'user_id' => $userId,
                ]);

                foreach ($answers as $questionId => $optionId) {
                    $vote->answers()->create([
                        'poll_question_id' => $questionId,
                        'poll_option_id' => $optionId,
                    ]);
                }

                return __('Your vote has been submitted.');
            });
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)
                && Vote::query()->where('poll_id', $poll->id)->where('user_id', $userId)->exists()) {
                return back()->with('status', __('You already voted.'));
            }

            throw $exception;
        }

        return back()->with('status', $message);
    }
}
