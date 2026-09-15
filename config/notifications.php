<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Recent notification window (days)
    |--------------------------------------------------------------------------
    |
    | The header notification center only lists database notifications created
    | within this many days. Anything older is hidden from the bell dropdown.
    |
    */

    'recent_days' => (int) env('NOTIFICATIONS_RECENT_DAYS', 1000),

    /*
    |--------------------------------------------------------------------------
    | Items per page
    |--------------------------------------------------------------------------
    |
    | How many notifications are returned per request in the bell dropdown.
    | The "show more" button loads the next page.
    |
    */

    'per_page' => (int) env('NOTIFICATIONS_PER_PAGE', 5),

];
