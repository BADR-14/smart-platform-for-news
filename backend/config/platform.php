<?php

/*
|--------------------------------------------------------------------------
| إعدادات المنصة
|--------------------------------------------------------------------------
| القيم هنا افتراضية. ما يظهر تحت "settings" يستطيع الأدمن تعديله من لوحة
| التحكم ويُخزَّن في جدول platform_settings.
*/

return [

    // رابط واجهة الويب (يُستخدم في رابط إعادة تعيين كلمة المرور)
    'frontend_url' => rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/'),

    // موقع لوحة الأدمن: موقع مستقل عن موقع الشركات (يُستخدم في رابط الدخول السحري)
    'admin_url' => rtrim((string) env('ADMIN_FRONTEND_URL', 'http://localhost:5174'), '/'),

    'admin_magic_link' => [
        'expires_in_minutes' => 10,
    ],

    'otp' => [
        'length' => 6,
        'expires_in_minutes' => 10,
    ],

    'verification' => [
        // التوثيق الآلي عبر Google Vision. عند تعطيله تُحال كل الطلبات للأدمن ليراجعها يدوياً.
        // يُفعَّل من .env بـ VERIFICATION_AUTO_ENABLED=true بعد تفعيل الفوترة على مشروع Google Cloud.
        'auto_enabled' => (bool) env('VERIFICATION_AUTO_ENABLED', true),
        // النتيجة من 100: أكبر أو يساوي auto_approve موافقة فورية،
        // بين manual_review و auto_approve تُحال للأدمن، وأقل من manual_review رفض فوري.
        'auto_approve_score' => 90,
        'manual_review_score' => 60,
        'max_attempts' => 3,
    ],

    'settings' => [
        // سعر تمييز المنشور الواحد عند الدفع من المحفظة مباشرة
        'featured_post_price' => 10.00,
        // مدة بقاء المنشور مميزاً بالأيام
        'featured_post_duration_days' => 7,
    ],

    'currency' => 'USD',
];
