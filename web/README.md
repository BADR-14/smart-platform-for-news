# Smart Platform for News — لوحة الويب

React 19 · TypeScript · Vite · Tailwind CSS 4 · TanStack Query · React Hook Form + Zod.

تطبيق واحد فيه لوحتان حسب نوع الحساب:

| اللوحة | المسار | لمن |
|---|---|---|
| لوحة الإدارة | `/admin` | حسابات الأدمن |
| لوحة الشركة | `/company` | الشركات الموافق على طلبها |

حساب "مستخدم" عادي لا يملك لوحة ويب (يستخدم تطبيق الموبايل).

## التشغيل

```bash
npm install
cp .env.example .env      # VITE_API_URL=http://127.0.0.1:8000/api
npm run dev               # http://localhost:5173
```

يجب أن يكون الباك-إند شغّالاً (`php artisan serve`) وأن يحتوي `CORS_ALLOWED_ORIGINS` في `.env` الخاص به على عنوان الويب.

| الأمر | الوظيفة |
|---|---|
| `npm run dev` | خادم التطوير |
| `npm run build` | فحص الأنواع ثم بناء نسخة الإنتاج في `dist/` |
| `npm run typecheck` | فحص TypeScript فقط |
| `npm run lint` | ESLint |

حسابات التجربة: `admin@system.com / admin1234` و `company@test.com / password`.

## البنية (Feature-based)

```
src/
├── app/                 نقطة تجميع التطبيق: المزوّدات والمسارات
├── config/              قراءة متغيرات البيئة
├── lib/
│   ├── api/             Axios الوحيد + ApiError + تخزين التوكن + أنواع الردود
│   ├── hooks/           useActionMutation, useListParams
│   └── utils/           cn, التنسيق (تواريخ، أرقام، عملة), أخطاء النماذج
├── components/
│   ├── ui/              مكتبة المكوّنات: Button, FormField, Modal, DataTable, StatCard ...
│   ├── layout/          DashboardLayout, AuthLayout, القوائم الجانبية
│   └── charts/
└── features/
    ├── auth/            الدخول، استعادة كلمة المرور، AuthProvider، حرّاس المسارات
    ├── admin/           api.ts + keys.ts + مجلد لكل صفحة (users, posts, reports, verification ...)
    ├── company/         api.ts + keys.ts + مجلد لكل صفحة (posts, wallet, plans, featured, profile)
    ├── notifications/   القائمة المنسدلة وصفحة الإشعارات
    ├── settings/        كلمة المرور وتفضيلات الإشعارات (مشتركة بين اللوحتين)
    └── shared/          الأنواع والتسميات المشتركة
```

قواعد متّبعة:

- **البيانات عبر TanStack Query فقط.** لا يوجد `useEffect + fetch` في الصفحات. كل ميزة لها مفاتيح استعلام في `keys.ts`.
- **طلبات الـ API في ملفات `api.ts` فقط**، والمكوّنات لا تعرف شيئاً عن Axios.
- **كل الألوان من `src/index.css`** (Design Tokens مأخوذة من الشعار). لا ألوان مكتوبة يدوياً داخل المكوّنات.
- **حالة القوائم في عنوان الصفحة** (البحث، الفلاتر، رقم الصفحة) عبر `useListParams`.
- **النماذج بـ React Hook Form + Zod**، وأخطاء التحقق القادمة من الباك تُعرض بجانب حقولها.
- **الحماية:** `RequireRole` يمنع عرض لوحة لا تخص المستخدم، ورد 401 من أي طلب يُنهي الجلسة. الحماية الفعلية في الباك-إند.
- **RTL:** الواجهة عربية بالكامل وتستخدم الخصائص المنطقية (`ms-`, `ps-`, `start-`). الأرقام والبريد والعملة داخل `.ltr`.

## ملاحظات

- شحن المحفظة يستخدم Stripe Elements؛ بيانات البطاقة تذهب إلى Stripe مباشرة ولا تمر بخوادمنا. يحتاج `STRIPE_KEY` و `STRIPE_SECRET` في الباك-إند (مفاتيح الاختبار).
- خريطة اختيار الموقع تستخدم OpenStreetMap (Leaflet + Nominatim) ولا تحتاج مفتاحاً.
- المشروع على Vite 7 لأن ملف البناء الخاص بـ Vite 8 (Rolldown) تحظره سياسة Application Control في بعض أجهزة ويندوز.

---

## موقعان مستقلان من نفس الكود

| الموقع | الأمر | العنوان | طريقة الدخول |
|---|---|---|---|
| الشركات | `npm run dev:company` | http://localhost:5173 | بريد وكلمة مرور + تسجيل شركة جديدة |
| الإدارة | `npm run dev:admin` | http://localhost:5174 | رابط سحري على البريد (بدون كلمة مرور) |

`npm run dev` يشغّل الاثنين معاً، و `npm run build` يبني `dist/company` و `dist/admin`.

- الفصل يتم وقت البناء بالـ `mode` (انظر `vite.config.ts` و `src/main.tsx`): كل موقع يُبنى بمساراته فقط
  (`src/app/company-router.tsx` / `src/app/admin-router.tsx`)، فصفحات الإدارة لا تدخل في ملفات موقع الشركات.
- جلسة كل موقع مستقلة (مفتاح تخزين مختلف)، وحساب لا يخص الموقع يُعرض له `/wrong-site`.
- **الإدارة**: `/login` يطلب الرابط، و `/auth/verify` يستهلكه. الباك-إند يرفض دخول الأدمن بكلمة مرور.
- **الشركات**: `/register` (حساب ← رمز تأكيد) ثم `/onboarding` (بيانات الشركة والوثائق ← انتظار المراجعة).
  نفس صفحة `/onboarding` تخدم مستخدم التطبيق الذي يريد توسيع حسابه إلى شركة.
