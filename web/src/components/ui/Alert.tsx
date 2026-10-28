import { AlertCircle, CheckCircle2, Info } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils/cn';

type Tone = 'danger' | 'success' | 'info' | 'warning';

const tones: Record<Tone, string> = {
  danger: 'border-danger-600/25 bg-danger-50 text-danger-700',
  success: 'border-success-600/25 bg-success-50 text-success-700',
  info: 'border-info-600/25 bg-info-50 text-info-600',
  warning: 'border-warning-600/25 bg-warning-50 text-warning-700',
};

const icons: Record<Tone, ReactNode> = {
  danger: <AlertCircle className="size-5 shrink-0" />,
  success: <CheckCircle2 className="size-5 shrink-0" />,
  info: <Info className="size-5 shrink-0" />,
  warning: <AlertCircle className="size-5 shrink-0" />,
};

interface AlertProps {
  tone?: Tone;
  title?: string;
  children: ReactNode;
  className?: string;
}

export function Alert({ tone = 'danger', title, children, className }: AlertProps) {
  return (
    <div role="alert" className={cn('flex gap-3 rounded-xl border px-4 py-3 text-sm', tones[tone], className)}>
      {icons[tone]}
      <div className="min-w-0 leading-relaxed">
        {title && <p className="font-semibold">{title}</p>}
        <div>{children}</div>
      </div>
    </div>
  );
}
