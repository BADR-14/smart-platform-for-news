import { useEffect, useRef, useState } from 'react';
import { formatNumber } from '@/lib/utils/format';

interface CountUpProps {
  value: number;
  format?: (value: number) => string;
  /** مدة العدّ بالميلي ثانية. */
  duration?: number;
}

const prefersReducedMotion = () =>
  typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * رقم يعدّ تصاعدياً من قيمته السابقة إلى الجديدة (من الصفر عند أول ظهور).
 * يحترم إعداد "تقليل الحركة" في النظام فيعرض القيمة مباشرة.
 */
export function CountUp({ value, format = formatNumber, duration = 900 }: CountUpProps) {
  const [display, setDisplay] = useState(() => (prefersReducedMotion() ? value : 0));
  const from = useRef(display);

  useEffect(() => {
    if (prefersReducedMotion()) {
      from.current = value;
      return;
    }

    const start = from.current;
    const startedAt = performance.now();
    let frame = 0;

    const tick = (now: number) => {
      const progress = Math.min(1, (now - startedAt) / duration);
      // easeOutCubic: سريع في البداية ويهدأ قرب النهاية
      const eased = 1 - (1 - progress) ** 3;
      const next = start + (value - start) * eased;

      from.current = next;
      setDisplay(next);

      if (progress < 1) frame = requestAnimationFrame(tick);
    };

    frame = requestAnimationFrame(tick);

    return () => cancelAnimationFrame(frame);
  }, [value, duration]);

  // الأعداد الصحيحة تُعرض بلا كسور أثناء العدّ
  const shown = prefersReducedMotion() ? value : Number.isInteger(value) ? Math.round(display) : display;

  return <>{format(shown)}</>;
}
