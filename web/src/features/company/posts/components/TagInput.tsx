import { X } from 'lucide-react';
import { useId, useState } from 'react';

interface TagInputProps {
  value: string[];
  onChange: (tags: string[]) => void;
  max?: number;
  error?: string;
}

/** إدخال الوسوم: اكتب ثم اضغط Enter أو فاصلة. */
export function TagInput({ value, onChange, max = 5, error }: TagInputProps) {
  const id = useId();
  const [draft, setDraft] = useState('');

  const add = () => {
    const tag = draft.trim().replace(/^#/, '').toLowerCase();

    if (tag && !value.includes(tag) && value.length < max) onChange([...value, tag]);
    setDraft('');
  };

  return (
    <div className="flex flex-col gap-1.5">
      <label htmlFor={id} className="text-sm font-medium text-ink-soft">
        الوسوم
      </label>

      <div className="flex min-h-10 flex-wrap items-center gap-1.5 rounded-xl border border-line-strong bg-surface px-2.5 py-1.5 focus-within:border-brand-600 focus-within:ring-2 focus-within:ring-brand-600/15">
        {value.map((tag) => (
          <span key={tag} className="inline-flex items-center gap-1 rounded-full bg-brand-100 py-0.5 pe-1 ps-2.5 text-xs font-medium text-brand-800">
            #{tag}
            <button
              type="button"
              onClick={() => onChange(value.filter((item) => item !== tag))}
              aria-label={`إزالة الوسم ${tag}`}
              className="rounded-full p-0.5 hover:bg-brand-200"
            >
              <X className="size-3" />
            </button>
          </span>
        ))}

        <input
          id={id}
          value={draft}
          maxLength={50}
          disabled={value.length >= max}
          placeholder={value.length >= max ? '' : 'أضف وسماً…'}
          onChange={(event) => setDraft(event.target.value)}
          onBlur={add}
          onKeyDown={(event) => {
            if (event.key === 'Enter' || event.key === ',' || event.key === '،') {
              event.preventDefault();
              add();
            } else if (event.key === 'Backspace' && draft === '' && value.length > 0) {
              onChange(value.slice(0, -1));
            }
          }}
          className="min-w-24 flex-1 bg-transparent py-1 text-sm text-ink outline-none placeholder:text-muted/70"
        />
      </div>

      {error ? (
        <p role="alert" className="text-xs text-danger-600">
          {error}
        </p>
      ) : (
        <p className="text-xs text-muted">
          حتى {max} وسوم. اضغط Enter بعد كل وسم. ({value.length}/{max})
        </p>
      )}
    </div>
  );
}
