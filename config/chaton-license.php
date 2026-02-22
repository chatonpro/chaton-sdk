<?php

return [
    /*
    |--------------------------------------------------------------------------
    | License Server URL
    |--------------------------------------------------------------------------
    |
    | The URL of ChatOn License Server (Hardcoded for security)
    | This cannot be changed via .env to prevent tampering
    |
    */
    'server_url' => 'https://api.chaton.pro',

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how license data is cached locally
    |
    */
    'cache' => [
        'driver' => env('CHATON_LICENSE_CACHE_DRIVER', 'redis'), // redis, file, database
        'ttl' => env('CHATON_LICENSE_CACHE_TTL', 86400), // 24 hours in seconds
        'key_prefix' => 'chaton_license_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Grace Period
    |--------------------------------------------------------------------------
    |
    | Number of days the application can run if license server is unreachable
    |
    */
    'grace_period_days' => env('CHATON_LICENSE_GRACE_PERIOD', 7),

    /*
    |--------------------------------------------------------------------------
    | Validation Schedule
    |--------------------------------------------------------------------------
    |
    | Daily validation job schedule (cron expression)
    |
    */
    'validation_schedule' => env('CHATON_LICENSE_VALIDATION_SCHEDULE', '0 2 * * *'), // 2 AM daily

    /*
    |--------------------------------------------------------------------------
    | RSA Public Key
    |--------------------------------------------------------------------------
    |
    | Embedded public key for signature verification
    | DO NOT MODIFY - This key verifies responses from the license server
    |
    */
    'public_key' => <<<'EOD'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAovvwpD4MIQ4RCTZvSul7
rvAKroN9v2l5xhtfLY6IKewhD6gElWbgitG6w0E/V0EjRFT3bUhVMGgA4jFwX1TN
bBb1Ure1aGcj0tSBu6KJAMfYHIfqkZg9PEs2k8Q/WIJXs0l9gAl3o/72KpsJvva2
RsIee2dp867Yt4lQKnONVCjE5HbJvnHLttjYv2tS+BCRQLhe5AJzNS5Pmk6nKR/l
bAlSc4VV9WhLTCwVTB+fWDzJN1QE5PEWJxF948/+rWEasbr9ss2E8lwABg01eNkd
bUo8nqlRa2m9If8bnkrq0B96VmE7b5EQX+a+dEW0sF8awaK5Si2Czi8OUIJCRXQg
1QIDAQAB
-----END PUBLIC KEY-----
EOD,

    /*
    |--------------------------------------------------------------------------
    | Timeout Configuration
    |--------------------------------------------------------------------------
    |
    | HTTP timeout settings for license server requests
    |
    */
    'timeout' => [
        'connect' => 10, // seconds
        'request' => 30, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Default feature availability (can be overridden by license server)
    |
    */
    'features' => [
        'saas' => [
            'regular' => false,  // Regular license: SAAS disabled
            'extended' => true,  // Extended license: SAAS enabled
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Strict Mode
    |--------------------------------------------------------------------------
    |
    | If enabled, application will stop completely if license is invalid
    | If disabled, will show warnings but allow limited functionality
    |
    */
    'strict_mode' => env('CHATON_LICENSE_STRICT_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | Domain Detection
    |--------------------------------------------------------------------------
    |
    | Automatically detect domain from request or use custom domain
    |
    */
    'domain' => env('CHATON_LICENSE_DOMAIN', null), // null = auto-detect
];
