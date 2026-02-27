<?php
// config services
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
    'eye_detection' => [
        'command' => base_path('python/venv/bin/python') . ' ' . base_path('python/detect_eye.py'),
    ],

    'zoom' => [
        'client_id' => env('ZOOM_4LIFE_OVH_CLIENT'),
        'client_secret' => env('ZOOM_4LIFE_OVH_SECRET'),
        'account_id' => env('ZOOM_4LIFE_OVH_ACCOUNT'),
    ],

    'twitter-oauth-2' => [
        'client_id' => env('TWITTER_CLIENT_ID'),
        'client_secret' => env('TWITTER_CLIENT_SECRET'),
        'redirect' => config('app.url') . env('TWITTER_REDIRECT_URI'),
    ],

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => config('app.url') . env('GITHUB_REDIRECT_URI'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_OAUTH_CLIENT_ID'),
        'client_secret' => env('GOOGLE_OAUTH_CLIENT_SECRET'),
        'redirect' => config('app.url') . env('GOOGLE_OAUTH_REDIRECT_URI'),
        'scopes' => explode(',', env('GOOGLE_OAUTH_SCOPES', '')),
    ],
    
    'gemini' => [
        'accounts' => explode(',', env('GEMINI_ALLOWED_EMAILS', '')),
        'key' => env('GEMINI_API_KEY'),
        'url' => env('GEMINI_API_URL'),
        'model' => env('GEMINI_MODEL_ID'),
    ],

    'recaptcha' => [
        'enterprise' => [
            'site_key' => env('GOOGLE_RECAPTCHA_ENTERPRISE_SITE_KEY'),
            'project_id' => env('GOOGLE_RECAPTCHA_ENTERPRISE_PROJECT_ID'),
            'credentials_path' => env('GOOGLE_RECAPTCHA_ENTERPRISE_CREDENTIALS_PATH'),
            'min_score' => env('GOOGLE_RECAPTCHA_ENTERPRISE_MIN_SCORE', 0.7),
            'max_attempts' => env('GOOGLE_RECAPTCHA_ENTERPRISE_MAX_ATTEMPTS', 5),
            'block_duration_days' => env('GOOGLE_RECAPTCHA_ENTERPRISE_BLOCK_DURATION_DAYS', 30),
        ],
    ],

    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect' => env('MICROSOFT_REDIRECT_URI'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openai' => [
        'api_key'      => env('OPENAI_API_KEY'),          // ✅ En `.env`
        'organization' => env('OPENAI_ORGANIZATION'),     // ✅ En `.env`
        'project'      => env('OPENAI_PROJECT_ID'),       // ✅ En `.env`
        'gpt'          => env('OPENAI_API_GPT'),          // ✅ En `.env`
        'model'        => env('OPENAI_API_MODEL'),        // ✅ En `.env`
        'tts_voices'   => [                               // ✔️ Lista fija, sin `.env`
            ['value' => 'shimmer', 'text' => 'Voz Shimmer'],
            ['value' => 'alloy',   'text' => 'Voz Alloy'],
            ['value' => 'echo',   'text' => 'Voz Echo'],
            ['value' => 'fable',  'text' => 'Voz Fable'],
            ['value' => 'onyx',   'text' => 'Voz Onyx'],
            ['value' => 'nova',   'text' => 'Voz Nova'],
        ],
        'advisor_model' => env('OPENAI_API_ADVISOR_MODEL', 'gpt-4o-mini'),
        'api_key2'     => env('OPENAI_API_KEY2'),
    ],
];
