<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

final class AcceptInviteRequest extends FormRequest
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
        $email = Str::lower(trim((string) $this->input('email', '')));
        $isExistingUser = $email !== '' && User::query()->where('email', $email)->exists();

        return [
            'email'    => ['required', 'string', 'email:rfc', 'max:255'],
            'name'     => ['nullable', 'string', 'max:255'],
            'password' => $isExistingUser
                ? ['required', 'string']
                : ['required', 'confirmed', Password::defaults()],
        ];
    }
}