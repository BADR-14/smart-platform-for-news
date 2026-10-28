import {
  ArrowLeft,
  BadgeCheck,
  BarChart3,
  CalendarDays,
  FileCheck2,
  GraduationCap,
  LogIn,
  Presentation,
  Rocket,
  Sparkles,
  Store,
  UserPlus,
  Wallet,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { Link } from 'react-router-dom';
import logoFull from '@/assets/logo-full.png';
import logoMark from '@/assets/logo-mark.png';
import { Button } from '@/components/ui/Button';

const contentTypes: { icon: ReactNode; title: string; text: string; tone: string }[] = [
  { icon: <Presentation className="size-6" />, title: 'محاضرات', text: 'محاضرات أكاديمية ومهنية يقدّمها مختصون.', tone: 'from-brand-600 to-brand-800' },
  { icon: <Store className="size-6" />, title: 'معارض', text: 'معارض تقنية وتجارية ومعارض توظيف.', tone: 'from-accent-400 to-accent-600' },
  { icon: <CalendarDays className="size-6" />, title: 'فعاليات', text: 'ملتقيات ومؤتمرات ولقاءات مجتمعية.', tone: 'from-teal-400 to-teal-600' },
  { icon: <GraduationCap className="size-6" />, title: 'دورات', text: 'دورات تدريبية وورشات عمل تفاعلية.', tone: 'from-violet-400 to-violet-600' },
];

const companyFeatures: { icon: ReactNode; title: string; text: string }[] = [
  { icon: <BadgeCheck className="size-5" />, title: 'حساب موثّق', text: 'شارة توثيق تمنح منشوراتك مصداقية أمام الجمهور.' },
  { icon: <Sparkles className="size-5" />, title: 'منشورات مميزة', text: 'اظهر أعلى الصفحة الرئيسية أمام كل مستخدمي التطبيق.' },
  { icon: <Wallet className="size-5" />, title: 'محفظة وباقات', text: 'اشحن رصيدك مرة واشترك بباقة تمييز بسعر أوفر.' },
  { icon: <BarChart3 className="size-5" />, title: 'إحصاءات لحظية', text: 'تابع المشاهدات والتفاعل والمتابعين من لوحة واحدة.' },
];

const steps: { icon: ReactNode; title: string; text: string }[] = [
  { icon: <UserPlus className="size-6" />, title: 'أنشئ الحساب', text: 'سجّل بالبريد وأكّده برمز يصلك خلال لحظات.' },
  { icon: <FileCheck2 className="size-6" />, title: 'أضف بيانات شركتك', text: 'الاسم والتصنيف والشعار والوثائق الرسمية.' },
  { icon: <Rocket className="size-6" />, title: 'ابدأ النشر', text: 'بعد موافقة الإدارة تُفتح لك لوحة الشركة كاملة.' },
];

/** الصفحة التعريفية لموقع الشركات. */
export function LandingPage() {
  return (
    <div className="min-h-screen">
      <header className="sticky top-0 z-20 border-b border-line/70 bg-surface/85 backdrop-blur-xl">
        <div className="mx-auto flex h-16 max-w-6xl items-center justify-between gap-3 px-4 sm:px-6">
          <div className="flex items-center gap-3">
            <img src={logoMark} alt="" className="size-10" />
            <span className="font-bold text-brand-800">
              Smart Platform <span className="text-accent-500">for News</span>
            </span>
          </div>
          <div className="flex items-center gap-2">
            <Link to="/login">
              <Button variant="ghost" icon={<LogIn className="size-4" />}>
                دخول
              </Button>
            </Link>
            <Link to="/register" className="hidden sm:block">
              <Button variant="accent">سجّل شركتك</Button>
            </Link>
          </div>
        </div>
      </header>

      {/* ---------- الواجهة ---------- */}
      <section className="relative overflow-hidden">
        <div aria-hidden className="bg-dots-dark absolute inset-0 [mask-image:radial-gradient(ellipse_at_center,black,transparent_75%)]" />
        <div aria-hidden className="animate-float absolute -start-24 top-10 size-80 rounded-full bg-accent-200/50 blur-3xl" />
        <div aria-hidden className="animate-float absolute -end-24 bottom-0 size-96 rounded-full bg-brand-200/60 blur-3xl [animation-delay:-3s]" />

        <div className="relative mx-auto grid max-w-6xl items-center gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)] lg:py-24">
          <div className="stagger">
            <span className="inline-flex items-center gap-2 rounded-full border border-accent-200 bg-accent-50 px-3.5 py-1.5 text-sm font-semibold text-accent-700">
              <span className="size-1.5 animate-pulse rounded-full bg-accent-500" />
              منصة الشركات للمحتوى المهني
            </span>
            <h1 className="mt-5 text-4xl font-bold leading-[1.25] text-brand-900 sm:text-5xl">
              أوصل فعاليات شركتك إلى جمهورها <span className="text-accent-500">في مكان واحد</span>
            </h1>
            <p className="mt-5 max-w-xl text-lg leading-relaxed text-ink-soft">
              انشر محاضراتك ومعارضك وفعالياتك ودوراتك، ميّز ما يستحق الظهور، وتابع تفاعل جمهورك لحظة بلحظة.
            </p>
            <div className="mt-8 flex flex-wrap gap-3">
              <Link to="/register">
                <Button size="lg" variant="accent" icon={<ArrowLeft className="size-5" />}>
                  سجّل شركتك مجاناً
                </Button>
              </Link>
              <Link to="/login">
                <Button size="lg" variant="outline" icon={<LogIn className="size-5" />}>
                  لديّ حساب
                </Button>
              </Link>
            </div>
            <p className="mt-4 text-sm text-muted">التسجيل يستغرق دقائق. لوحة الشركة تُفتح بعد موافقة الإدارة على الوثائق.</p>
          </div>

          <div className="relative mx-auto w-full max-w-sm">
            {/* حلقتان مداريتان حول الشعار */}
            <div aria-hidden className="absolute -inset-6 animate-spin rounded-full border border-dashed border-brand-200 [animation-duration:50s]" />
            <div aria-hidden className="absolute -inset-14 rounded-full border border-brand-100" />
            <div className="animate-pop-in relative flex aspect-square items-center justify-center rounded-[3rem] bg-white p-10 shadow-(--shadow-lift) ring-1 ring-line">
              <img src={logoFull} alt="Smart Platform for News" className="max-h-full max-w-full object-contain" />
            </div>
            <div className="animate-float absolute -start-6 top-10 flex items-center gap-2 rounded-2xl bg-white px-3.5 py-2.5 text-sm font-semibold text-ink shadow-(--shadow-lift)">
              <span className="flex size-8 items-center justify-center rounded-xl bg-success-50 text-success-600">
                <BadgeCheck className="size-4" />
              </span>
              شركة موثّقة
            </div>
            <div className="animate-float absolute -end-4 bottom-12 flex items-center gap-2 rounded-2xl bg-white px-3.5 py-2.5 text-sm font-semibold text-ink shadow-(--shadow-lift) [animation-delay:-2.5s]">
              <span className="bg-accent-gradient flex size-8 items-center justify-center rounded-xl text-white">
                <Sparkles className="size-4" />
              </span>
              منشور مميز
            </div>
          </div>
        </div>
      </section>

      {/* ---------- أنواع المحتوى ---------- */}
      <section className="bg-brand-50/50 py-16">
        <div className="mx-auto max-w-6xl px-4 sm:px-6">
          <h2 className="text-center text-2xl font-bold text-ink">ماذا تنشر شركتك على المنصة؟</h2>
          <p className="mx-auto mt-2 max-w-xl text-center text-sm text-muted">أربعة أنواع من المحتوى المهني، كلها تصل لمستخدمي تطبيق الموبايل.</p>
          <div className="stagger mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {contentTypes.map((item) => (
              <article
                key={item.title}
                className="group rounded-(--radius-card) border border-line/70 bg-surface p-6 shadow-(--shadow-card) transition-all duration-300 hover:-translate-y-1.5 hover:shadow-(--shadow-lift)"
              >
                <div
                  className={`flex size-13 items-center justify-center rounded-2xl bg-linear-to-br text-white shadow-lg transition-transform duration-300 group-hover:-rotate-6 group-hover:scale-110 ${item.tone}`}
                >
                  {item.icon}
                </div>
                <h3 className="mt-4 text-lg font-bold text-ink">{item.title}</h3>
                <p className="mt-1.5 text-sm leading-relaxed text-muted">{item.text}</p>
              </article>
            ))}
          </div>
        </div>
      </section>

      {/* ---------- خطوات التسجيل ---------- */}
      <section className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <h2 className="text-center text-2xl font-bold text-ink">ثلاث خطوات لتبدأ</h2>
        <ol className="stagger mt-10 grid gap-5 md:grid-cols-3">
          {steps.map((step, index) => (
            <li key={step.title} className="relative rounded-(--radius-card) border border-line/70 bg-surface p-6 shadow-(--shadow-card)">
              <span className="ltr absolute end-5 top-4 text-5xl font-bold text-brand-50">{index + 1}</span>
              <span className="relative flex size-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">{step.icon}</span>
              <h3 className="relative mt-4 text-lg font-bold text-ink">{step.title}</h3>
              <p className="relative mt-1.5 text-sm leading-relaxed text-muted">{step.text}</p>
            </li>
          ))}
        </ol>
      </section>

      {/* ---------- مزايا الشركات ---------- */}
      <section className="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
        <div className="bg-brand-gradient relative overflow-hidden rounded-[2rem] px-6 py-12 sm:px-12">
          <div aria-hidden className="bg-dots absolute inset-0" />
          <div aria-hidden className="animate-float absolute -end-16 -top-16 size-72 rounded-full bg-accent-500/25 blur-3xl" />

          <div className="relative">
            <h2 className="text-2xl font-bold text-white">كل ما تحتاجه شركتك في لوحة واحدة</h2>
            <p className="mt-2 max-w-2xl text-brand-100">من النشر إلى التمييز إلى متابعة النتائج.</p>
            <div className="stagger mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              {companyFeatures.map((item) => (
                <div key={item.title} className="rounded-2xl border border-white/10 bg-white/8 p-5 backdrop-blur transition-colors hover:bg-white/14">
                  <div className="bg-accent-gradient flex size-10 items-center justify-center rounded-xl text-white">{item.icon}</div>
                  <h3 className="mt-3 font-bold text-white">{item.title}</h3>
                  <p className="mt-1 text-sm leading-relaxed text-brand-100">{item.text}</p>
                </div>
              ))}
            </div>
            <Link to="/register" className="mt-8 inline-block">
              <Button size="lg" variant="accent" icon={<ArrowLeft className="size-5" />}>
                ابدأ الآن
              </Button>
            </Link>
          </div>
        </div>
      </section>

      <footer className="border-t border-line py-6 text-center text-sm text-muted">
        © {new Date().getFullYear()} Smart Platform for News — مشروع تخرج
      </footer>
    </div>
  );
}
