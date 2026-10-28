import { forwardRef, useId } from 'react';
import type { InputHTMLAttributes, ReactNode, SelectHTMLAttributes, TextareaHTMLAttributes } from 'react';
import { cn } from '@/lib/utils/cn';

const controlClass = cn(
  'w-full rounded-xl border border-line-strong bg-surface px-3.5 text-sm text-ink transition-colors',
  'placeholder:text-muted/70 hover:border-brand-300',
  'focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/15',
  'disabled:cursor-not-allowed disabled:bg-canvas disabled:text-muted',
  'aria-[invalid=true]:border-danger-600 aria-[invalid=true]:focus:ring-danger-600/15',
);

interface FieldShellProps {
  id: string;
  label?: string;
  hint?: string;
  error?: string;
  required?: boolean;
  className?: string;
  children: ReactNode;
}

/** الغلاف الموحّد لكل حقول النماذج: عنوان + الحقل + تلميح أو رسالة خطأ. */
function FieldShell({ id, label, hint, error, required, className, children }: FieldShellProps) {
  return (
    <div className={cn('flex flex-col gap-1.5', className)}>
      {label && (
        <label htmlFor={id} className="text-sm font-medium text-ink-soft">
          {label}
          {required && <span className="ms-1 text-danger-600">*</span>}
        </label>
      )}
      {children}
      {error ? (
        <p id={`${id}-error`} role="alert" className="text-xs text-danger-600">
          {error}
        </p>
      ) : (
        hint && <p className="text-xs text-muted">{hint}</p>
      )}
    </div>
  );
}

interface CommonProps {
  label?: string;
  hint?: string;
  error?: string;
  wrapperClassName?: string;
}

export type InputProps = InputHTMLAttributes<HTMLInputElement> & CommonProps & { leading?: ReactNode };

export const Input = forwardRef<HTMLInputElement, InputProps>(function Input(
  { label, hint, error, wrapperClassName, className, id, required, leading, ...props },
  ref,
) {
  const generatedId = useId();
  const fieldId = id ?? generatedId;

  return (
    <FieldShell id={fieldId} label={label} hint={hint} error={error} required={required} className={wrapperClassName}>
      <div className="relative">
        {leading && (
          <span className="pointer-events-none absolute inset-y-0 start-3 flex items-center text-muted">{leading}</span>
        )}
        <input
          ref={ref}
          id={fieldId}
          required={required}
          aria-invalid={Boolean(error)}
          aria-describedby={error ? `${fieldId}-error` : undefined}
          // حقول الأرقام والتواريخ تُعرض بأرقام لاتينية مثل باقي الواجهة
          lang={props.type === 'number' || props.type === 'date' ? 'en' : undefined}
          className={cn(controlClass, 'h-10', leading && 'ps-10', className)}
          {...props}
        />
      </div>
    </FieldShell>
  );
});

export type TextareaProps = TextareaHTMLAttributes<HTMLTextAreaElement> & CommonProps;

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(function Textarea(
  { label, hint, error, wrapperClassName, className, id, required, rows = 4, ...props },
  ref,
) {
  const generatedId = useId();
  const fieldId = id ?? generatedId;

  return (
    <FieldShell id={fieldId} label={label} hint={hint} error={error} required={required} className={wrapperClassName}>
      <textarea
        ref={ref}
        id={fieldId}
        rows={rows}
        required={required}
        aria-invalid={Boolean(error)}
        className={cn(controlClass, 'resize-y py-2.5 leading-relaxed', className)}
        {...props}
      />
    </FieldShell>
  );
});

export type SelectProps = SelectHTMLAttributes<HTMLSelectElement> & CommonProps;

export const Select = forwardRef<HTMLSelectElement, SelectProps>(function Select(
  { label, hint, error, wrapperClassName, className, id, required, children, ...props },
  ref,
) {
  const generatedId = useId();
  const fieldId = id ?? generatedId;

  return (
    <FieldShell id={fieldId} label={label} hint={hint} error={error} required={required} className={wrapperClassName}>
      <select
        ref={ref}
        id={fieldId}
        required={required}
        aria-invalid={Boolean(error)}
        className={cn(controlClass, 'h-10 cursor-pointer pe-8', className)}
        {...props}
      >
        {children}
      </select>
    </FieldShell>
  );
});
