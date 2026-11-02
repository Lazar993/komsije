<?php

declare(strict_types=1);

namespace App\Http\Requests\Poll;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreVoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $poll = $this->route('poll');
        $questions = $poll->questions()->pluck('id');

        if ($questions->isEmpty()) {
            return [
                'poll_option_id' => ['required', 'integer', Rule::exists('poll_options', 'id')->where('poll_id', $poll->id)],
            ];
        }

        $rules = ['answers' => ['required', 'array:'.$questions->implode(','), 'size:'.$questions->count()]];

        foreach ($questions as $questionId) {
            $rules['answers.'.$questionId] = [
                'required',
                'integer',
                Rule::exists('poll_options', 'id')->where('poll_id', $poll->id)->where('poll_question_id', $questionId),
            ];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('answers') && $this->has('poll_option_id')) {
            $questions = $this->route('poll')->questions()->pluck('id');

            if ($questions->count() === 1) {
                $this->merge(['answers' => [$questions->first() => $this->input('poll_option_id')]]);
            }
        }
    }

    public function messages(): array
    {
        return [
            'answers.required' => __('Answer every question before submitting.'),
            'answers.array' => __('The answers must belong to this poll.'),
            'answers.size' => __('Answer every question before submitting.'),
            'answers.*.required' => __('Answer every question before submitting.'),
            'answers.*.integer' => __('The selected option is invalid for this question.'),
            'answers.*.exists' => __('The selected option is invalid for this question.'),
        ];
    }
}
