<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Test User Settings
    |--------------------------------------------------------------------------
    |
    | Centralized settings for the shared test user and related cleanup.
    | Values are sourced from environment variables and accessed via config().
    |
    */

    'email' => env('USER_TEST_EMAIL'),
    'prompt_cleanup_ttl_minutes' => env('TEST_USER_PROMPT_CLEANUP_TTL_MINUTES', 1),
];

