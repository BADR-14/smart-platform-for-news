# Smart Platform for News

منصة مهنية لنشر المحاضرات والمعارض والفعاليات والدورات ومتابعتها: الشركات تنشر محتواها وتميّزه، والمستخدمون يتابعون ويتفاعلون ويقيّمون، والإدارة توثّق الحسابات وتراقب المحتوى.

## مكوّنات المشروع

| المجلد | الوصف | التقنية |
|---|---|---|
| `backend/` | الـ API وقاعدة البيانات | Laravel 12، MySQL، Sanctum |
| `web/` | موقع الشركات ولوحة الإدارة (موقعان من قاعدة كود واحدة) | React 19، TypeScript، Vite، Tailwind |
| `mobile/` | تطبيق المستخدمين | Flutter، GetX |


## التشغيل

### 1. الـ Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000
```

يحتاج قاعدة بيانات MySQL، وإعدادات البريد وGoogle وFirebase في ملف `.env` (انظر `.env.example`).
أنشئ قاعدة بيانات فارغة باسم `smart_news` (أو غيّر `DB_DATABASE` في `.env`) قبل تشغيل `migrate`.

#### ملفات Google وFirebase (لا تُرفع إلى الريبو)

ضع ملفي حساب الخدمة الخاصين بمشروعك على Google Cloud في المسار التالي:

- `backend/storage/app/google/vision.json`: لقراءة وثائق التوثيق (Vision API)
- `backend/storage/app/google/firebase.json`: لإشعارات Push (Firebase)

بدونهما تُحال طلبات التوثيق إلى الأدمن يدوياً، وتتعطل الإشعارات، ويبقى باقي النظام يعمل.

#### معالجة الطلبات في الخلفية

الإعداد الافتراضي `QUEUE_CONNECTION=database` يحتاج تشغيل:

```bash
php artisan queue:work
```

أو غيّر القيمة إلى `sync` في `.env` للتجربة المحلية بدون worker. 

### 2. الويب

```bash
cd web
cp .env.example .env   # على ويندوز: copy .env.example .env
npm install
npm run dev
```

- موقع الشركات: `http://localhost:5173`
- لوحة الإدارة: `http://localhost:5174` (الدخول برابط يُرسل إلى بريد المدير)

### 3. تطبيق الموبايل

```bash
cd mobile
flutter pub get
cp dart_defines.example.json dart_defines.json   # على ويندوز: copy dart_defines.example.json dart_defines.json
flutter run --dart-define-from-file=dart_defines.json
```

قبل التشغيل:

- عدّل `dart_defines.json`: ضع عنوان الـ API (على الموبايل الحقيقي استخدم IP جهازك على الشبكة، وعلى الـ emulator استخدم `10.0.2.2`) وGoogle Client ID الخاص بمشروعك.
- ضع ملف `google-services.json` الخاص بمشروعك على Firebase في `android/app/`.

## الحسابات التجريبية

بعد `php artisan migrate --seed` (كلمة المرور: `password`):

- مستخدم: `user@test.com`
- مستخدم موثّق: `verified@test.com`
- شركة: `company@test.com`

> هذه الحسابات وكلمة المرور `password` للتجربة المحلية فقط. وغيّر `ADMIN_PASSWORD` في `.env` قبل أي نشر حقيقي، ولا تشغّل الـ seeders على بيئة إنتاج.

