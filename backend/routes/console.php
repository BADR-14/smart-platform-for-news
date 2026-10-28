<?php

use Illuminate\Support\Facades\Schedule;

// إنهاء التمييز عن المنشورات التي انتهت مدتها (يحتاج تشغيل: php artisan schedule:work)
Schedule::command('featured:expire')->hourly();

// تنظيف التوكنات المنتهية الصلاحية
Schedule::command('sanctum:prune-expired --hours=24')->daily();
