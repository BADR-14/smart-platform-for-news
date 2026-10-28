import { Area, AreaChart, CartesianGrid, Legend, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { chartTooltipStyle } from './chart-utils';
import type { GrowthSeries } from './GrowthChart';

interface AreaTrendChartProps {
  data: Record<string, string | number>[];
  series: GrowthSeries[];
  height?: number;
}

/**
 * مخطط مساحات بتدرّج لوني لعرض الاتجاه الشهري لعدة سلاسل.
 * المنحنيات تُرسم بحركة عند الظهور.
 */
export function AreaTrendChart({ data, series, height = 320 }: AreaTrendChartProps) {
  return (
    // الرسم نفسه من اليسار لليمين حتى تبقى المحاور مقروءة؛ النصوص عربية
    <div className="ltr" style={{ height }}>
      <ResponsiveContainer width="100%" height="100%">
        <AreaChart data={data} margin={{ top: 10, right: 12, bottom: 0, left: -18 }}>
          <defs>
            {series.map((item) => (
              <linearGradient key={item.key} id={`area-${item.key}`} x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stopColor={item.color} stopOpacity={0.38} />
                <stop offset="100%" stopColor={item.color} stopOpacity={0} />
              </linearGradient>
            ))}
          </defs>
          <CartesianGrid strokeDasharray="4 6" stroke="var(--color-line)" vertical={false} />
          <XAxis dataKey="month" tick={{ fontSize: 12, fill: 'var(--color-muted)' }} tickLine={false} axisLine={false} dy={6} />
          <YAxis allowDecimals={false} tick={{ fontSize: 12, fill: 'var(--color-muted)' }} tickLine={false} axisLine={false} />
          <Tooltip contentStyle={chartTooltipStyle} cursor={{ stroke: 'var(--color-brand-300)', strokeDasharray: '4 4' }} />
          <Legend iconType="circle" wrapperStyle={{ fontFamily: 'var(--font-sans)', fontSize: 13, direction: 'rtl', paddingTop: 12 }} />
          {series.map((item, index) => (
            <Area
              key={item.key}
              type="monotone"
              dataKey={item.key}
              name={item.label}
              stroke={item.color}
              strokeWidth={2.6}
              fill={`url(#area-${item.key})`}
              dot={false}
              activeDot={{ r: 5, strokeWidth: 2, stroke: '#fff' }}
              animationBegin={index * 180}
              animationDuration={1100}
            />
          ))}
        </AreaChart>
      </ResponsiveContainer>
    </div>
  );
}
