<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Portal;

use App\Http\Requests\Poll\StoreVoteRequest;
use App\Models\Poll;
use App\Models\Vote;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

final class PollVoteController extends PortalController
{
    public function __construct(private readonly TenantContext $tenantContext)
    {
    }

    public function __invoke(StoreVoteRequest $request, Poll $poll): RedirectResponse
    {
        abort_if((int) $poll->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('vote', $poll);

        if (! $poll->isOpenForVoting()) {
            return back()->with('status', __('Voting is closed for this poll.'));
        }

        $optionId = (int) $request->validated('poll_option_id');
        $optionBelongsToPoll = $poll->options()->whereKey($optionId)->exists();

        if (! $optionBelongsToPoll) {
            throw ValidationException::withMessages([
                'poll_option_id' => __('The selected option is invalid for this poll.'),
            ]);
        }

        $userId = (int) $request->user()->getKey();

        if (Vote::query()->where('poll_id', $poll->getKey())->where('user_id', $userId)->exists()) {
            return back()->with('status', __('You already voted.'));
        }

        try {
            Vote::query()->create([
                'poll_id' => $poll->getKey(),
                'poll_option_id' => $optionId,
                'user_id' => $userId,
            ]);
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return back()->with('status', __('You already voted.'));
            }

            throw $exception;
        }

        return back()->with('status', __('Your vote has been submitted.'));
    }
}
