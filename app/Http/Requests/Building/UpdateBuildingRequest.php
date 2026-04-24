<?php

declare(strict_types=1);

namespace App\Http\Requests\Building;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateBuildingRequest extends FormRequest
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
            'address' => ['required', 'string', 'max:255'],
            'billing_customer_reference' => ['nullable', 'string', 'max:255'],
            'manager_ids' => ['sometimes', 'array'],
            'manager_ids.*' => ['integer', Rule::exists('users', 'id')],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}