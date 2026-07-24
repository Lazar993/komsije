<?php

declare(strict_types=1);

namespace App\Http\Requests\NeighborBoard;

use App\Enums\NeighborBoardCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateNeighborBoardPostRequest extends FormRequest
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
            'category' => ['sometimes', Rule::enum(NeighborBoardCategory::class)],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:5000'],
            'images' => ['nullable', 'array', 'max:3'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
        ];
    }
}
