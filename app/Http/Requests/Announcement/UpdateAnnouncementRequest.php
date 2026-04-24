<?php

declare(strict_types=1);

namespace App\Http\Requests\Announcement;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAnnouncementRequest extends FormRequest
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
            'content' => ['sometimes', 'string', 'max:10000'],
            'published_at' => ['nullable', 'date'],
            'title' => ['sometimes', 'string', 'max:255'],
        ];
    }
}