<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Activities
    |--------------------------------------------------------------------------
    |
    | Recurring non-billable activities offered as quick-add chips in the time
    | entry form (admin, gym, lunch, …). A time entry is EITHER attached to a
    | project OR tagged with one of these activities. Activity entries count
    | toward the day's total hours but never produce revenue and can't be billed.
    |
    | Add or rename entries here to change the available chips.
    |
    */

    'activities' => [
        'Admin',
        'Gym',
        'Lunch',
    ],

];
