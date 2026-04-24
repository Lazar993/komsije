<?php

declare(strict_types=1);

namespace App\Http\Requests\Apartment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateApartmentRequest extends FormRequest
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
            'available_for_marketplace' => ['sometimes', 'boolean'],
            'building_id' => ['required', 'integer'],
            'floor' => ['nullable', 'string', 'max:50'],
            'marketplace_listing_reference' => ['nullable', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:50'],
            'tenant_ids' => ['sometimes', 'array'],
            'tenant_ids.*' => ['integer', Rule::exists('users', 'id')],
        ];
    }
}