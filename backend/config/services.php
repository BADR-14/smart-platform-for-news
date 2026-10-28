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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URL'),
        // كل الـ Client IDs المسموح أن تصدر id_token لتسجيل الدخول (ويب، أندرويد، iOS) مفصولة بفاصلة
        'allowed_client_ids' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('GOOGLE_ALLOWED_CLIENT_IDS', (string) env('GOOGLE_CLIENT_ID')))
        ))),
    ],

    'google_vision' => [
        // مسار ملف مفتاح حساب الخدمة (JSON). إذا لم يوجد تُحال طلبات التوثيق للأدمن يدوياً.
        'credentials' => env('GOOGLE_APPLICATION_CREDENTIALS', storage_path('app/google/vision.json')),
    ],

    'fcm' => [
        // مفتاح حساب خدمة Firebase (JSON) لإرسال إشعارات Push. اتركه فارغاً لتعطيل الـ Push.
        'credentials' => env('FCM_CREDENTIALS', storage_path('app/google/firebase.json')),
        'project_id' => env('FCM_PROJECT_ID'),
    ],
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
];
