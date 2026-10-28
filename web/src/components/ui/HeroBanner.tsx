import type { ReactNode } from 'react';

interface HeroBannerProps {
  eyebrow?: string;
  title: string;
  description?: string;
  actions?: ReactNode;
  /** محتوى الجهة المقابلة للنص (حلقة تقدّم، أرقام بارزة ...). */
  aside?: ReactNode;
}

/** ترويسة ترحيبية بتدرّج كحلي وزخارف متحركة — أول ما يراه المستخدم في لوحته. */
export function HeroBanner({ eyebrow, title, description, actions, aside }: HeroBannerProps) {
  return (
    <section className="bg-brand-gradient relative mb-6 overflow-hidden rounded-[1.75rem] px-6 py-7 text-white shadow-(--shadow-lift) sm:px-8 sm:py-8">
      <div aria-hidden className="bg-dots absolute inset-0 opacity-70" />
      <div aria-hidden className="animate-float absolute -end-16 -top-20 size-64 rounded-full bg-accent-500/30 blur-3xl" />
      <div aria-hidden className="animate-float absolute -bottom-24 start-1/3 size-72 rounded-full bg-brand-400/25 blur-3xl [animation-delay:-3s]" />
      {/* حلقة مدارية كحلقة الشعار */}
      <div aria-hidden className="absolute -end-10 top-1/2 size-72 -translate-y-1/2 animate-spin rounded-full border border-dashed border-white/15 [animation-duration:70s]" />

      <div className="relative flex flex-wrap items-center justify-between gap-6">
        <div className="min-w-0 max-w-2xl">
          {eyebrow && (
            <span className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-medium text-accent-200 backdrop-blur">
              <span className="size-1.5 animate-pulse rounded-full bg-accent-400" />
              {eyebrow}
            </span>
          )}
          <h1 className="mt-3 text-2xl font-bold leading-snug sm:text-[1.75rem]">{title}</h1>
          {description && <p className="mt-2 text-sm leading-relaxed text-brand-100 sm:text-[0.95rem]">{description}</p>}
          {actions && <div className="mt-5 flex flex-wrap items-center gap-2.5">{actions}</div>}
        </div>
        {aside && <div className="relative shrink-0">{aside}</div>}
      </div>
    </section>
  );
}
