<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BuildingRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['building_id', 'title', 'description', 'majority_type', 'is_anonymous', 'is_active', 'ends_at', 'ending_reminder_sent_at', 'closed_notified_at'])]
class Poll extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(PollQuestion::class)->orderBy('id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function eligibleVoters(): Builder
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('buildings', fn (Builder $query): Builder => $query
                ->where('buildings.id', $this->building_id)
                ->where('building_user.role', BuildingRole::Tenant->value));
    }

    public function eligibleVotes(): HasMany
    {
        return $this->votes()->whereIn('user_id', $this->eligibleVoters()->select('users.id'));
    }

    public function participationLabel(): string
    {
        return __(':voted/:total residents voted', [
            'voted' => $this->eligibleVotes()->count(),
            'total' => $this->eligibleVoters()->count(),
        ]);
    }

    public function results(): array
    {
        $threshold = $this->requiredVotes($this->eligibleVoters()->count());
        $questions = $this->questions()->with(['options' => fn ($query) => $query
            ->withCount(['answers' => fn ($answers) => $answers->whereHas('vote', fn ($votes) => $votes
                ->whereIn('user_id', $this->eligibleVoters()->select('users.id')))])])->get();

        if ($questions->isEmpty()) {
            $options = $this->options()->withCount(['votes' => fn ($query) => $query
                ->whereIn('user_id', $this->eligibleVoters()->select('users.id'))])->get();

            return [[
                'question' => $this->title,
                'successful' => $options->max('votes_count') >= $threshold,
                'options' => $options->map(fn (PollOption $option): array => [
                    'option' => $option->text,
                    'votes' => $option->votes_count,
                ])->all(),
            ]];
        }

        return $questions->map(fn (PollQuestion $question): array => [
            'question' => $question->text,
            'successful' => $question->options->max('answers_count') >= $threshold,
            'options' => $question->options->map(fn (PollOption $option): array => [
                'option' => $option->text,
                'votes' => $option->answers_count,
            ])->all(),
        ])->all();
    }

    public function outcome(): ?bool
    {
        if ($this->majority_type === null || $this->isOpenForVoting()) {
            return null;
        }

        return collect($this->results())->every('successful', true);
    }

    public function outcomeLabel(): string
    {
        return match ($this->outcome()) {
            true => __('Successful'),
            false => __('Unsuccessful'),
            null => $this->majority_type === null ? __('Informational poll') : __('Voting in progress'),
        };
    }

    public function requiredVotes(int $eligibleCount): int
    {
        return $this->majority_type === 'two_thirds'
            ? max(1, (int) ceil($eligibleCount * 2 / 3))
            : intdiv($eligibleCount, 2) + 1;
    }

    public function isOpenForVoting(): bool
    {
        return (bool) $this->is_active
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn (Builder $builder): Builder => $builder
                ->whereNull('ends_at')
                ->orWhere('ends_at', '>', now()));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'closed_notified_at' => 'datetime',
            'ending_reminder_sent_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_anonymous' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
