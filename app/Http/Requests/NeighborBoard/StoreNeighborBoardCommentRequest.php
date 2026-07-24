<?php

declare(strict_types=1);

namespace App\Http\Requests\NeighborBoard;

use Illuminate\Foundation\Http\FormRequest;

final class StoreNeighborBoardCommentRequest extends FormRequest
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
            'building_id' => ['required', 'integer'],
            'body' => ['required', 'string', 'max:2000'],
        ];
    }
}
