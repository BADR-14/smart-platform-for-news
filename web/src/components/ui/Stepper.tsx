import { Check } from 'lucide-react';
import { cn } from '@/lib/utils/cn';

interface StepperProps {
  steps: string[];
  /** رقم الخطوة الحالية (يبدأ من 0). ما قبلها مكتمل وما بعدها قادم. */
  current: number;
  className?: string;
}

/** مؤشر خطوات لعملية من عدة مراحل (تسجيل شركة جديدة). */
export function Stepper({ steps, current, className }: StepperProps) {
  return (
    <ol className={cn('flex items-start', className)} aria-label="خطوات التسجيل">
      {steps.map((label, index) => {
        const done = index < current;
        const active = index === current;

        return (
          <li key={label} className="relative flex flex-1 flex-col items-center gap-1.5 text-center" aria-current={active ? 'step' : undefined}>
            {/* الخط الواصل بالخطوة السابقة */}
            {index > 0 && (
              <span
                aria-hidden
                className={cn('absolute end-1/2 top-4 h-0.5 w-full -translate-y-1/2', done || active ? 'bg-accent-500' : 'bg-line')}
              />
            )}
            <span
              className={cn(
                'relative z-10 flex size-8 items-center justify-center rounded-full text-xs font-bold transition-colors',
                done && 'bg-accent-500 text-white',
                active && 'bg-brand-800 text-white ring-4 ring-brand-100',
                !done && !active && 'border border-line-strong bg-surface text-muted',
              )}
            >
              {done ? <Check className="size-4" /> : index + 1}
            </span>
            <span className={cn('text-[11px] font-medium leading-tight', active ? 'text-ink' : 'text-muted')}>{label}</span>
          </li>
        );
      })}
    </ol>
  );
}
