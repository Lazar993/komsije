<?php

declare(strict_types=1);

namespace App\Http\Requests\Ticket;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $description = Str::of((string) $this->input('description'))->squish();
        $title = Str::of((string) $this->input('title'))->squish();

        $payload = [];

        if ($title->isEmpty() && $description->isNotEmpty()) {
            $payload['title'] = (string) $description->limit(80, '');
        }

        if (! $this->filled('priority')) {
            $payload['priority'] = TicketPriority::Medium->value;
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'apartment_id' => ['nullable', 'integer'],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp,pdf,mp4,mov,doc,docx,xls,xlsx', 'max:10240'],
            'building_id' => ['required', 'integer'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'status' => ['sometimes', Rule::enum(TicketStatus::class)],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }
}