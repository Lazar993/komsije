<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Building;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

/**
 * Server-side guard preventing managers/tenants from creating records against a
 * read-only (suspended or archived) building. Super admins bypass, mirroring
 * Gate::before. Applied to building_id fields in the admin create forms so a
 * tampered request cannot slip past the restricted select options.
 */
final class BuildingAcceptsWrites implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $user = Auth::user();

        if ($user !== null && $user->isSuperAdmin()) {
            return;
        }

        $building = Building::query()->find($value);

        if ($building !== null && ! $building->allowsWrites()) {
            $fail(__('This building is currently read-only. New records cannot be created.'));
        }
    }
}
