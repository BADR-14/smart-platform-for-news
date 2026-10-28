import { CornerDownLeft, Search } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { cn } from '@/lib/utils/cn';
import type { NavItem } from './navigation';

interface CommandPaletteProps {
  open: boolean;
  onClose: () => void;
  items: NavItem[];
}

/**
 * لوحة الأوامر (Ctrl+K): انتقال سريع بين صفحات اللوحة بالكتابة والأسهم و Enter.
 * تُركَّب فقط عند الفتح، فتبدأ كل مرة ببحث فارغ.
 */
export function CommandPalette({ open, onClose, items }: CommandPaletteProps) {
  if (!open) return null;

  return <PaletteDialog onClose={onClose} items={items} />;
}

function PaletteDialog({ onClose, items }: Omit<CommandPaletteProps, 'open'>) {
  const [query, setQuery] = useState('');
  const [active, setActive] = useState(0);
  const inputRef = useRef<HTMLInputElement>(null);
  const navigate = useNavigate();

  const results = useMemo(() => {
    const term = query.trim().toLowerCase();

    return term ? items.filter((item) => item.label.toLowerCase().includes(term)) : items;
  }, [items, query]);

  useEffect(() => {
    inputRef.current?.focus();
  }, []);

  const go = (item: NavItem | undefined) => {
    if (!item) return;
    navigate(item.to);
    onClose();
  };

  const onKeyDown = (event: React.KeyboardEvent) => {
    if (event.key === 'Escape') onClose();
    if (event.key === 'Enter') go(results[active]);
    if (event.key === 'ArrowDown') {
      event.preventDefault();
      setActive((index) => (index + 1) % Math.max(results.length, 1));
    }
    if (event.key === 'ArrowUp') {
      event.preventDefault();
      setActive((index) => (index - 1 + results.length) % Math.max(results.length, 1));
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-start justify-center px-4 pt-[14vh]" role="dialog" aria-modal="true" aria-label="انتقال سريع">
      <div className="animate-fade-in absolute inset-0 bg-graphite-950/55 backdrop-blur-sm" onClick={onClose} />

      <div className="animate-pop-in relative w-full max-w-xl overflow-hidden rounded-2xl border border-line bg-surface shadow-(--shadow-pop)">
        <div className="flex items-center gap-3 border-b border-line px-4">
          <Search className="size-5 shrink-0 text-muted" />
          <input
            ref={inputRef}
            value={query}
            onChange={(event) => {
              setQuery(event.target.value);
              setActive(0);
            }}
            onKeyDown={onKeyDown}
            placeholder="إلى أين تريد الذهاب؟"
            className="h-14 w-full bg-transparent text-base text-ink outline-none placeholder:text-muted"
          />
          <kbd className="ltr shrink-0 rounded-md border border-line bg-canvas px-1.5 py-0.5 text-[11px] font-medium text-muted">Esc</kbd>
        </div>

        <ul className="max-h-80 overflow-y-auto p-2">
          {results.length === 0 && <li className="px-3 py-8 text-center text-sm text-muted">لا توجد صفحة بهذا الاسم.</li>}
          {results.map((item, index) => (
            <li key={item.to}>
              <button
                type="button"
                onMouseEnter={() => setActive(index)}
                onClick={() => go(item)}
                className={cn(
                  'flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-start text-sm font-medium transition-colors',
                  index === active ? 'bg-graphite-900 text-white' : 'text-ink-soft',
                )}
              >
                <span className={cn(index === active ? 'text-accent-400' : 'text-muted')}>{item.icon}</span>
                <span className="flex-1">{item.label}</span>
                {index === active && <CornerDownLeft className="size-4 text-graphite-300" />}
              </button>
            </li>
          ))}
        </ul>

        <div className="flex items-center gap-4 border-t border-line bg-canvas/70 px-4 py-2 text-[11px] text-muted">
          <span>↑ ↓ للتنقل</span>
          <span>Enter للفتح</span>
        </div>
      </div>
    </div>
  );
}
