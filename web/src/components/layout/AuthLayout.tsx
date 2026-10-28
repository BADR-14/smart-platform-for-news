import { BadgeCheck, BarChart3, LockKeyhole, ShieldCheck, Sparkles, Wallet } from 'lucide-react';
import type { ReactNode } from 'react';
import { Link } from 'react-router-dom';
import logoFull from '@/assets/logo-full.png';
import logoMark from '@/assets/logo-mark.png';
import { isAdminSite } from '@/config/env';

interface AuthLayoutProps {
  title: string;
  description?: string;
  children: ReactNode;
  footer?: ReactNode;
}

/** ما يُعرض في اللوحة التعريفية بجانب النموذج — يختلف بين موقع الشركات وموقع الإدارة. */
const panel = isAdminSite
  ? {
      heading: 'لوحة إدارة المنصة',
      text: 'موقع مستقل ومحمي لإدارة المستخدمين والشركات والمحتوى. الدخول بالرابط السحري فقط.',
      points: [
        { icon: <LockKeyhole className="size-5" />, label: 'بدون كلمات مرور تُسرَّب أو تُخمَّن' },
        { icon: <ShieldCheck className="size-5" />, label: 'رابط لمرة واحدة ينتهي بعد 10 دقائق' },
        { icon: <BarChart3 className="size-5" />, label: 'تقارير ومتابعة لحظية للمنصة' },
      ],
    }
  : {
      heading: 'منصة الأعمال للفعاليات والمحتوى المهني',
      text: 'محاضرات، معارض، فعاليات ودورات — في مكان واحد. من هنا تدير شركتك حضورها على المنصة.',
      points: [
        { icon: <BadgeCheck className="size-5" />, label: 'حساب شركة موثّق بشارة رسمية' },
        { icon: <Sparkles className="size-5" />, label: 'منشورات مميزة تتصدر الصفحة الرئيسية' },
        { icon: <Wallet className="size-5" />, label: 'محفظة وباقات بأسعار أوفر' },
      ],
    };

/** تخطيط صفحات الدخول: نموذج في جهة ولوحة تعريفية حيّة بألوان الشعار في الجهة الأخرى. */
export function AuthLayout({ title, description, children, footer }: AuthLayoutProps) {
  return (
    <div className="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)]">
      <main className="flex flex-col justify-center px-6 py-10 sm:px-12">
        <div className="animate-rise mx-auto w-full max-w-md">
          <Link to="/" className="mb-9 inline-flex items-center gap-3">
            <img src={logoMark} alt="" className="size-11" />
            <span className="text-lg font-bold text-brand-800">
              Smart Platform <span className="text-accent-500">for News</span>
            </span>
          </Link>

          <h1 className="text-[1.7rem] font-bold leading-tight text-ink">{title}</h1>
          {description && <p className="mt-2 text-sm leading-relaxed text-muted">{description}</p>}

          <div className="mt-8">{children}</div>

          {footer && <div className="mt-6 text-sm text-muted">{footer}</div>}
        </div>
      </main>

      <aside className="bg-brand-gradient relative hidden overflow-hidden lg:flex lg:flex-col lg:items-center lg:justify-center">
        <div aria-hidden className="bg-dots absolute inset-0" />
        <div aria-hidden className="animate-float absolute -start-20 top-16 size-72 rounded-full bg-accent-500/25 blur-3xl" />
        <div
          aria-hidden
          className="animate-float absolute -bottom-24 -end-16 size-96 rounded-full bg-brand-400/25 blur-3xl [animation-delay:-3.5s]"
        />
        {/* حلقتان متداخلتان كحلقة الشعار */}
        <div aria-hidden className="absolute size-[34rem] rounded-full border border-white/10" />
        <div aria-hidden className="absolute size-[26rem] animate-spin rounded-full border border-dashed border-white/15 [animation-duration:60s]" />

        <div className="relative max-w-md px-10 text-center">
          <div className="animate-pop-in mx-auto mb-8 flex size-52 items-center justify-center rounded-[2.75rem] bg-white p-6 shadow-(--shadow-pop)">
            <img src={logoFull} alt="Smart Platform for News" className="max-h-full max-w-full object-contain" />
          </div>
          <h2 className="animate-rise text-2xl font-bold leading-snug text-white [animation-delay:0.1s]">{panel.heading}</h2>
          <p className="animate-rise mt-3 leading-relaxed text-brand-200 [animation-delay:0.18s]">{panel.text}</p>

          <ul className="stagger mt-8 space-y-3 text-start">
            {panel.points.map((point) => (
              <li
                key={point.label}
                className="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/8 px-4 py-3 text-sm font-medium text-white backdrop-blur"
              >
                <span className="bg-accent-gradient flex size-9 shrink-0 items-center justify-center rounded-xl">{point.icon}</span>
                {point.label}
              </li>
            ))}
          </ul>
        </div>
      </aside>
    </div>
  );
}
