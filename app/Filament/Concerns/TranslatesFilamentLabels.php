<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use UnitEnum;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

trait TranslatesFilamentLabels
{
    public static function getNavigationGroup(): string | UnitEnum | null
    {
        $group = parent::getNavigationGroup();

        return is_string($group) ? __($group) : $group;
    }

    public static function getNavigationLabel(): string
    {
        return __(parent::getNavigationLabel());
    }

    public static function getModelLabel(): string
    {
        return static::translateResourceLabel(parent::getModelLabel());
    }

    public static function getPluralModelLabel(): string
    {
        return static::translateResourceLabel(parent::getPluralModelLabel());
    }

    private static function translateResourceLabel(string $label): string
    {
        if (Lang::has($label)) {
            return __($label);
        }

        $headline = Str::ucfirst(Str::lower(Str::headline($label)));

        return Lang::has($headline) ? __($headline) : $label;
    }
}