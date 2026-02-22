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
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAs8dZPO7vVXjRgk2ySXCR
RU3G9+pte4CtnJJOygZbkK3gMrKzPYOuiycn2eOxInTSNQOqFAiHfzd0OiTNcE+G
QV/mbro7OjFbSSFWUVDfe9LvJDn2GzXXi+gMWJ1v91T03ilpeY4PB0cLmhIUb+pW
hdZBg6+L9583c8u052ipO37RATwDYrfXdVKSBBPtnOIW71Zm1b5KBsOUCNr8eojv
BnivPXT1ysEZX7EqAGy7rrtO0Ay2MDjHvZejvU3MCLBdQf2BezR5kt9CItw6DJh8
WNFJAp5kO7P5fsJs0C65Mc9ZknUN78VKUopx4tiEuEVT1ro9RaHATW5Pp0516ZEJ
PwIDAQAB
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
