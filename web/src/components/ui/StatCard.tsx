import { ArrowUpLeft } from 'lucide-react';
import type { ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { isAdminSite } from '@/config/env';
import { cn } from '@/lib/utils/cn';
import { CountUp } from './CountUp';
import { Skeleton } from './States';

type Tone = 'brand' | 'accent' | 'success' | 'warning' | 'danger' | 'info' | 'violet';

/** لون مربع الأيقونة (تدرّج) + لون الهالة الزخرفية في زاوية البطاقة. */
const tones: Record<Tone, { icon: string; glow: string }> = {
  brand: { icon: 'from-brand-600 to-brand-800 shadow-brand-800/30', glow: 'bg-brand-200/50' },
  accent: { icon: 'from-accent-400 to-accent-600 shadow-accent-500/40', glow: 'bg-accent-200/60' },
  success: { icon: 'from-emerald-400 to-success-600 shadow-success-600/35', glow: 'bg-emerald-200/50' },
  warning: { icon: 'from-amber-400 to-warning-600 shadow-warning-600/35', glow: 'bg-amber-200/60' },
  danger: { icon: 'from-rose-400 to-danger-600 shadow-danger-600/35', glow: 'bg-rose-200/50' },
  info: { icon: 'from-sky-400 to-info-600 shadow-info-600/35', glow: 'bg-sky-200/50' },
  violet: { icon: 'from-violet-400 to-violet-600 shadow-violet-600/35', glow: 'bg-violet-200/50' },
};

/** لوحة الإدارة: بطاقة مسطّحة محايدة يميّزها خط لوني رفيع فقط. */
const adminTones: Record<Tone, { bar: string; icon: string }> = {
  brand: { bar: 'bg-graphite-800', icon: 'text-graphite-700' },
  accent: { bar: 'bg-accent-500', icon: 'text-accent-600' },
  success: { bar: 'bg-success-600', icon: 'text-success-600' },
  warning: { bar: 'bg-warning-600', icon: 'text-warning-600' },
  danger: { bar: 'bg-danger-600', icon: 'text-danger-600' },
  info: { bar: 'bg-brand-500', icon: 'text-brand-600' },
  violet: { bar: 'bg-graphite-500', icon: 'text-graphite-600' },
};

interface StatCardProps {
  label: string;
  /** رقم يُعدّ تصاعدياً عند الظهور، أو أي محتوى جاهز (نص، كسر ...). */
  value: number | ReactNode;
  /** تنسيق الرقم أثناء العدّ (عملة مثلاً). الافتراضي: فواصل الآلاف. */
  format?: (value: number) => string;
  icon: ReactNode;
  tone?: Tone;
  hint?: string;
  to?: string;
  loading?: boolean;
}

export function StatCard({ label, value, format, icon, tone = 'brand', hint, to, loading = false }: StatCardProps) {
  const style = tones[tone];

  if (isAdminSite) {
    const admin = adminTones[tone];
    const adminContent = (
      <>
        <span aria-hidden className={cn('absolute inset-x-0 top-0 h-0.75', admin.bar)} />
        <div className="flex items-center justify-between gap-3">
          <p className="truncate text-sm font-semibold text-muted">{label}</p>
          <span className={cn('flex size-9 shrink-0 items-center justify-center rounded-lg bg-graphite-50 [&_svg]:size-4.5', admin.icon)}>{icon}</span>
        </div>
        {loading ? (
          <Skeleton className="mt-3 h-8 w-20" />
        ) : (
          <p className="ltr mt-2 text-end text-3xl font-bold leading-none tabular-nums tracking-tight text-ink">
            {typeof value === 'number' ? <CountUp value={value} format={format} /> : value}
          </p>
        )}
        {hint && <p className="mt-2 truncate text-xs text-muted">{hint}</p>}
      </>
    );
    const adminClass = cn(
      'group relative block overflow-hidden rounded-(--radius-card) border border-line bg-surface px-5 pb-4 pt-5 shadow-(--shadow-card) transition-all duration-200',
      to && 'hover:border-graphite-900 hover:shadow-(--shadow-lift)',
    );

    return to ? (
      <Link to={to} className={adminClass}>
        {adminContent}
      </Link>
    ) : (
      <div className={adminClass}>{adminContent}</div>
    );
  }

  const content = (
    <>
      {/* هالة لونية زخرفية تكبر عند المرور */}
      <span
        aria-hidden
        className={cn(
          'pointer-events-none absolute -end-8 -top-10 size-28 rounded-full blur-2xl transition-transform duration-500 group-hover:scale-150',
          style.glow,
        )}
      />

      <div className="relative flex items-start justify-between gap-3">
        <div
          className={cn(
            'flex size-12 shrink-0 items-center justify-center rounded-2xl bg-linear-to-br text-white shadow-lg transition-transform duration-300 group-hover:-rotate-6 group-hover:scale-110',
            style.icon,
          )}
        >
          {icon}
        </div>
        {to && (
          <ArrowUpLeft className="size-4 text-muted opacity-0 transition-all duration-300 group-hover:-translate-x-0.5 group-hover:-translate-y-0.5 group-hover:opacity-100" />
        )}
      </div>

      <div className="relative mt-4 min-w-0">
        {loading ? (
          <Skeleton className="h-8 w-20" />
        ) : (
          <p className="ltr text-end text-[1.7rem] font-bold leading-none tabular-nums text-ink">
            {typeof value === 'number' ? <CountUp value={value} format={format} /> : value}
          </p>
        )}
        <p className="mt-2 truncate text-sm font-medium text-ink-soft">{label}</p>
        {hint && <p className="mt-0.5 truncate text-xs text-muted">{hint}</p>}
      </div>
    </>
  );

  const className = cn(
    'group relative block overflow-hidden rounded-(--radius-card) border border-line/70 bg-surface p-5 shadow-(--shadow-card) transition-all duration-300',
    to && 'hover:-translate-y-1 hover:border-brand-200 hover:shadow-(--shadow-lift)',
  );

  return to ? (
    <Link to={to} className={className}>
      {content}
    </Link>
  ) : (
    <div className={className}>{content}</div>
  );
}
