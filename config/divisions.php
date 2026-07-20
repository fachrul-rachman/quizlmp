<?php

return [
    'default' => [
        'participant_applied_for_label' => 'Jabatan/Peringkat',
        'participant_intro_title' => 'Sebelum mulai',
    ],

    /*
    |--------------------------------------------------------------------------
    | Division-specific participant experience
    |--------------------------------------------------------------------------
    |
    | Add only the labels or behavior that genuinely differ per division.
    | Shared behavior remains in "default" so HR and Business Development do
    | not drift accidentally.
    |
    */
    'profiles' => [
        'hr' => [
            'participant_applied_for_label' => 'Jabatan',
        ],
        'business_development' => [],
    ],
];
