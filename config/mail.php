<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | Which of the mailers below is used unless a message names another one.
    |
    | MAIL_MAILER is the current name for this setting. MAIL_DRIVER is the name
    | Laravel used up to 6.x and is still what the deployed .env files set, so
    | it is honoured as a fallback and either will work. Prefer MAIL_MAILER in
    | new environments.
    |
    */

    'default' => env('MAIL_MAILER', env('MAIL_DRIVER', 'smtp')),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Supported transports: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    | "postmark", "resend", "log", "array", "failover", "roundrobin".
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            // Leave unset for STARTTLS on 587. Port 465 selects "smtps" on its
            // own. MAIL_ENCRYPTION is no longer read — the scheme replaced it.
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 587),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        // Resend's API transport. Sending over plain SMTP needs none of this —
        // point the smtp mailer above at smtp.resend.com instead. To use this
        // one: composer require resend/resend-laravel, set RESEND_KEY, and set
        // MAIL_MAILER=resend.
        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
            'retry_after' => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | Must be an address on a domain verified with the sending provider, or
    | messages will fail authentication checks at the recipient.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Blind Copy Address
    |--------------------------------------------------------------------------
    |
    | Every invoice mail sent from the app is blind copied here, so there is
    | always a record of what went out. Leave empty to send no copy.
    |
    */

    'bcc' => [
        'address' => env('MAIL_BCC_ADDRESS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Markdown Mail Settings
    |--------------------------------------------------------------------------
    |
    */

    'markdown' => [
        'theme' => env('MAIL_MARKDOWN_THEME', 'default'),

        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],

];
