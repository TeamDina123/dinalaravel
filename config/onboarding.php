<?php

return [
    // Publish Dina's approved plain-text waiver here. Never substitute generic legal copy.
    'waiver_path' => env('ONBOARDING_WAIVER_PATH', storage_path('app/onboarding/waiver.txt')),
];
