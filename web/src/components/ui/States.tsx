import { AlertTriangle, Inbox, Loader2 } from 'lucide-react';
import type { ReactNode } from 'react';
import { errorMessage } from '@/lib/api/errors';
import { cn } from '@/lib/utils/cn';
import { Button } from './Button';

export function Spinner({ className }: { className?: string }) {
  return <Loader2 className={cn('size-5 animate-spin text-brand-600', className)} aria-label="جارِ التحميل" />;
}

/** تحميل يملأ مساحة الصفحة أو البطاقة. */
export function LoadingState({ label = 'جارِ التحميل…', className }: { label?: string; className?: string }) {
  return (
    <div className={cn('flex flex-col items-center justify-center gap-3 py-16 text-muted', className)}>
      <Spinner className="size-7" />
      <p className="text-sm">{label}</p>
    </div>
  );
}

interface EmptyStateProps {
  title: string;
  description?: string;
  icon?: ReactNode;
  action?: ReactNode;
  className?: string;
}

export function EmptyState({ title, description, icon, action, className }: EmptyStateProps) {
  return (
    <div className={cn('animate-fade-in flex flex-col items-center justify-center gap-4 px-4 py-14 text-center', className)}>
      <div className="relative">
        <span aria-hidden className="absolute inset-0 animate-ping rounded-3xl bg-brand-100 opacity-40 [animation-duration:2.4s]" />
        <div className="relative flex size-16 items-center justify-center rounded-3xl bg-linear-to-br from-brand-50 to-brand-100 text-brand-500 ring-1 ring-brand-200/60">
          {icon ?? <Inbox className="size-8" />}
        </div>
      </div>
      <div>
        <p className="font-semibold text-ink">{title}</p>
        {description && <p className="mt-1 max-w-sm text-sm text-muted">{description}</p>}
      </div>
      {action}
    </div>
  );
}

interface ErrorStateProps {
  error: unknown;
  onRetry?: () => void;
  className?: string;
}

export function ErrorState({ error, onRetry, className }: ErrorStateProps) {
  return (
    <div className={cn('flex flex-col items-center justify-center gap-3 px-4 py-14 text-center', className)}>
      <div className="flex size-14 items-center justify-center rounded-2xl bg-danger-50 text-danger-600">
        <AlertTriangle className="size-7" />
      </div>
      <div>
        <p className="font-semibold text-ink">تعذّر تحميل البيانات</p>
        <p className="mt-1 max-w-sm text-sm text-muted">{errorMessage(error)}</p>
      </div>
      {onRetry && (
        <Button variant="outline" size="sm" onClick={onRetry}>
          إعادة المحاولة
        </Button>
      )}
    </div>
  );
}

export function Skeleton({ className }: { className?: string }) {
  return <div className={cn('skeleton rounded-lg', className)} />;
}
