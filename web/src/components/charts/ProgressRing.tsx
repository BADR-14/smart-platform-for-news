import type { ReactNode } from 'react';

interface ProgressRingProps {
  /** النسبة من 0 إلى 1. */
  value: number;
  size?: number;
  stroke?: number;
  color?: string;
  children?: ReactNode;
}

/** حلقة تقدّم تُرسم بحركة عند الظهور (استهلاك الباقة مثلاً). */
export function ProgressRing({ value, size = 120, stroke = 11, color = 'var(--color-accent-500)', children }: ProgressRingProps) {
  const ratio = Math.min(1, Math.max(0, value));
  const radius = (size - stroke) / 2;
  const circumference = 2 * Math.PI * radius;

  return (
    <div className="relative shrink-0" style={{ width: size, height: size }}>
      <svg width={size} height={size} className="-rotate-90" role="img" aria-label={`${Math.round(ratio * 100)}%`}>
        <circle cx={size / 2} cy={size / 2} r={radius} fill="none" stroke="var(--color-line)" strokeWidth={stroke} />
        <circle
          cx={size / 2}
          cy={size / 2}
          r={radius}
          fill="none"
          stroke={color}
          strokeWidth={stroke}
          strokeLinecap="round"
          strokeDasharray={circumference}
          strokeDashoffset={circumference * (1 - ratio)}
          className="animate-draw"
          // الحركة تبدأ من حلقة فارغة تماماً
          style={{ ['--draw-from' as string]: circumference }}
        />
      </svg>
      <div className="absolute inset-0 flex flex-col items-center justify-center text-center">{children}</div>
    </div>
  );
}
