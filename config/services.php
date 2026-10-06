<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | TalkSasa Bulk SMS
    |--------------------------------------------------------------------------
    |
    | Verified against the account's own API docs and by probing the live host:
    |
    |   - auth is `Authorization: Bearer {token}` plus an Accept: json header
    |   - every reply is HTTP 200, including errors; success is decided by the
    |     `status` field ("success" / "error") in the body, never by the code
    |   - recipients carry no leading + (docs example: 8801721970168)
    |   - `sms/send` and `balance` exist; `profile` and `sms-units` do not
    |
    | Endpoint paths stay in config so they can be corrected without touching
    | the client.
    |
    | driver: 'log' writes messages to the log instead of sending, so local
    | development and the test suite never spend real SMS credits. Set
    | SMS_DRIVER=talksasa in production only.
    |
    */
    'talksasa' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'base_url' => env('TALKSASA_BASE_URL', 'https://bulksms.talksasa.com/api/v3'),
        'token' => env('TALKSASA_TOKEN'),
        'sender_id' => env('TALKSASA_SENDER_ID', 'ELDOGAS'),
        'timeout' => (int) env('TALKSASA_TIMEOUT', 15),

        // Some installs of the platform want +254..., others reject the plus.
        // Flip this if a live send is rejected for an invalid recipient.
        'plus_prefix' => (bool) env('TALKSASA_PLUS_PREFIX', false),

        // Shared secret in the inbound callback URL. The gateway cannot log
        // in, so this is what stands between the endpoint and anyone who finds
        // it. Empty means the endpoint 404s, which is the safe default: no
        // secret, no callback.
        'inbound_secret' => env('TALKSASA_INBOUND_SECRET'),

        'endpoints' => [
            'send' => env('TALKSASA_SEND_PATH', 'sms/send'),
            'show' => env('TALKSASA_SHOW_PATH', 'sms/{uid}'),
            'balance' => env('TALKSASA_BALANCE_PATH', 'balance'),
        ],
    ],

];
