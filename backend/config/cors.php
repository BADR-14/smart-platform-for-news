<?php

return [

    /*
    | الـ API يعتمد على Bearer Token وليس على الكوكيز، لذلك supports_credentials = false.
    | الأصول المسموحة تُحدَّد من ملف .env (مفصولة بفاصلة). تطبيق الموبايل لا يتأثر بـ CORS.
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173,http://127.0.0.1:5173'))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => false,

];
