import type { ReactNode } from 'react';
import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts';
import { formatNumber } from '@/lib/utils/format';
import { chartTooltipStyle } from './chart-utils';

export interface DonutSlice {
  key: string;
  label: string;
  value: number;
  color: string;
}

interface DonutChartProps {
  slices: DonutSlice[];
  /** ما يُكتب في وسط الحلقة (الإجمالي مثلاً). */
  center?: ReactNode;
  centerLabel?: string;
  size?: number;
}

/** مخطط حلقي مع مفتاح ألوان يعرض القيمة والنسبة لكل شريحة. */
export function DonutChart({ slices, center, centerLabel, size = 190 }: DonutChartProps) {
  const total = slices.reduce((sum, slice) => sum + slice.value, 0);
  // حلقة رمادية كاملة عندما لا توجد بيانات، حتى لا تظهر مساحة فارغة
  const data = total > 0 ? slices.filter((slice) => slice.value > 0) : [{ key: 'empty', label: '', value: 1, color: 'var(--color-line)' }];

  return (
    <div className="flex flex-wrap items-center justify-center gap-6">
      <div className="ltr relative shrink-0" style={{ width: size, height: size }}>
        <ResponsiveContainer width="100%" height="100%">
          <PieChart>
            <Pie
              data={data}
              dataKey="value"
              nameKey="label"
              innerRadius="68%"
              outerRadius="100%"
              paddingAngle={total > 0 && data.length > 1 ? 3 : 0}
              cornerRadius={6}
              stroke="none"
              startAngle={90}
              endAngle={-270}
              animationDuration={1000}
            >
              {data.map((slice) => (
                <Cell key={slice.key} fill={slice.color} />
              ))}
            </Pie>
            {total > 0 && <Tooltip contentStyle={chartTooltipStyle} />}
          </PieChart>
        </ResponsiveContainer>
        <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
          <span className="text-2xl font-bold tabular-nums text-ink">{center ?? formatNumber(total)}</span>
          {centerLabel && <span className="text-xs text-muted">{centerLabel}</span>}
        </div>
      </div>

      <ul className="min-w-40 flex-1 space-y-2.5">
        {slices.map((slice) => (
          <li key={slice.key} className="flex items-center gap-3 text-sm">
            <span className="size-2.5 shrink-0 rounded-full" style={{ backgroundColor: slice.color }} />
            <span className="flex-1 text-ink-soft">{slice.label}</span>
            <span className="font-semibold tabular-nums text-ink">{formatNumber(slice.value)}</span>
            <span className="w-10 text-end text-xs tabular-nums text-muted">
              {total > 0 ? Math.round((slice.value / total) * 100) : 0}%
            </span>
          </li>
        ))}
      </ul>
    </div>
  );
}
