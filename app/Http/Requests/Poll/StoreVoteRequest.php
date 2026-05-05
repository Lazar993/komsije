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
        return [
            'poll_option_id' => ['required', 'integer', Rule::exists('poll_options', 'id')],
        ];
    }
}
