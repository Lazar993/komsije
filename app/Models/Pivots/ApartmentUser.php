<?php

declare(strict_types=1);

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ApartmentUser extends Pivot
{
    protected $table = 'apartment_user';

    public $incrementing = true;
}