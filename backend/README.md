# Smart Platform for News — Backend API

Laravel 12 · Sanctum · MySQL · Stripe · Google Vision.

منصة أعمال للمحاضرات والمعارض والفعاليات والدورات. هذا المشروع هو الـ API الذي تعتمد عليه لوحة الويب (React) وتطبيق الموبايل (Flutter).

## التشغيل

```bash
composer install
cp .env.example .env        # ثم عدّل بيانات قاعدة البيانات
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

لمعالجة طلبات التوثيق وإنهاء المنشورات المميزة في الخلفية:

```bash
php artisan queue:work
php artisan schedule:work
```

> للتجربة السريعة بدون `queue:work` ضع `QUEUE_CONNECTION=sync` في `.env`.

## حسابات تجريبية (يزرعها `DemoSeeder`)

| الحساب | البريد | كلمة المرور |
|---|---|---|
| أدمن | `admin@system.com` | `admin1234` |
| شركة موثّقة (رصيد + باقة) | `company@test.com` | `password` |
| مستخدم موثّق | `verified@test.com` | `password` |
| مستخدم غير موثّق | `user@test.com` | `password` |

رموز OTP تُكتب في `storage/logs/laravel.log` ما دام `MAIL_MAILER=log`.

## الاختبارات

```bash
php artisan test
```

120 اختباراً تغطي المصادقة، المنشورات، التفاعلات، التوثيق، الشركات، المحفظة، الباقات، المنشورات المميزة، البلاغات، ولوحة الأدمن. تعمل على SQLite في الذاكرة ولا تحتاج أي خدمة خارجية.

## المعمارية

```
Route → Middleware → Controller → FormRequest → Service → Model → Resource
```

| الطبقة | المسؤولية |
|---|---|
| `Http/Controllers` | استقبال الطلب وإرجاع الرد فقط — بدون منطق عمل |
| `Http/Requests` | التحقق من المدخلات والصلاحية |
| `Services` | منطق العمل كله |
| `Http/Resources` | شكل الـ JSON |
| `Policies` | من يحق له ماذا (المنشورات، التعليقات، المشاركات) |
| `Enums` | الحالات والأنواع بدل النصوص المبعثرة |
| `Exceptions/ApiException` | أخطاء منطق العمل تتحول تلقائياً إلى رد JSON موحّد |

أنماط مستخدمة:

- **Strategy** — `Services/Featured/Payment`: طريقتا دفع المنشور المميز (المحفظة / الباقة) خلف واجهة واحدة.
- **Adapter** — `Contracts/DocumentTextExtractor`: قارئ الوثائق (Google Vision) قابل للتبديل والتزييف في الاختبارات.
- **Observer** — عدّادات الشركة وإنشاء المحفظة.
- **Event / Listener** — `SendPushNotification`: كل إشعار يُحفظ يُرسل كـ Push دون أن يعرف صنف الإشعار عن FCM.

## خدمات خارجية (اختيارية)

| الخدمة | ما يلزم | إذا لم تُضبط |
|---|---|---|
| Google Vision | `storage/app/google/vision.json` | كل طلبات التوثيق تُحال للأدمن يدوياً |
| Stripe | `STRIPE_KEY`, `STRIPE_SECRET` (مفاتيح الاختبار) | شحن المحفظة يُرجع 503 |
| Firebase (Push) | `storage/app/google/firebase.json` | الإشعارات تبقى داخل التطبيق فقط |
| Google Sign-In | `GOOGLE_ALLOWED_CLIENT_IDS` | يُرفض الدخول بـ Google |

مرجع المسارات: [`docs/API.md`](../docs/API.md) · توثيق تفاعلي: `/docs/api`.
