import type { CSSProperties } from 'react';

/** يحوّل رد الـ API (مصفوفات متوازية) إلى صفوف يفهمها Recharts. */
export function toChartRows(labels: string[], values: Record<string, number[]>): Record<string, string | number>[] {
  return labels.map((month, index) => ({
    month,
    ...Object.fromEntries(Object.entries(values).map(([key, list]) => [key, list[index] ?? 0])),
  }));
}

/** شكل موحّد لنافذة القيم التي تظهر عند المرور على أي مخطط. */
export const chartTooltipStyle: CSSProperties = {
  borderRadius: 14,
  border: '1px solid var(--color-line)',
  boxShadow: 'var(--shadow-lift)',
  fontFamily: 'var(--font-sans)',
  fontSize: 13,
  direction: 'rtl',
};
