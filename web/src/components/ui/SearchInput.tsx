import { Search, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { cn } from '@/lib/utils/cn';

interface SearchInputProps {
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
  className?: string;
  /** مدة الانتظار بعد توقف الكتابة قبل إرسال القيمة (حتى لا يُرسل طلب مع كل حرف). */
  delay?: number;
}

export function SearchInput({ value, onChange, placeholder = 'بحث…', className, delay = 350 }: SearchInputProps) {
  const [draft, setDraft] = useState(value);
  const [syncedValue, setSyncedValue] = useState(value);

  // إذا تغيّرت القيمة من الخارج (زر الرجوع، مسح الفلاتر) نحدّث النص المعروض
  if (value !== syncedValue) {
    setSyncedValue(value);
    setDraft(value);
  }

  useEffect(() => {
    if (draft === value) return;

    const timer = setTimeout(() => onChange(draft), delay);
    return () => clearTimeout(timer);
  }, [draft, value, onChange, delay]);

  return (
    <div className={cn('relative w-full sm:w-72', className)}>
      <Search className="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-muted" aria-hidden />
      <input
        type="search"
        value={draft}
        onChange={(event) => setDraft(event.target.value)}
        placeholder={placeholder}
        aria-label={placeholder}
        className={cn(
          'h-10 w-full rounded-xl border border-line-strong bg-surface ps-9 pe-9 text-sm text-ink',
          'placeholder:text-muted/70 hover:border-brand-300',
          'focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/15',
          '[&::-webkit-search-cancel-button]:hidden',
        )}
      />
      {draft && (
        <button
          type="button"
          onClick={() => {
            setDraft('');
            onChange('');
          }}
          aria-label="مسح البحث"
          className="absolute end-2 top-1/2 -translate-y-1/2 rounded-md p-1 text-muted hover:bg-canvas hover:text-ink"
        >
          <X className="size-4" />
        </button>
      )}
    </div>
  );
}
