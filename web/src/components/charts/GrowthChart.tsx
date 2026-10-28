import { CartesianGrid, Legend, Line, LineChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

export interface GrowthSeries {
  key: string;
  label: string;
  color: string;
}

interface GrowthChartProps {
  data: Record<string, string | number>[];
  series: GrowthSeries[];
  height?: number;
}

/** مخطط خطي للنمو الشهري. الألوان تأتي من Design Tokens. */
export function GrowthChart({ data, series, height = 300 }: GrowthChartProps) {
  return (
    // الرسم نفسه من اليسار لليمين حتى تبقى المحاور مقروءة؛ النصوص عربية
    <div className="ltr" style={{ height }}>
      <ResponsiveContainer width="100%" height="100%">
        <LineChart data={data} margin={{ top: 8, right: 16, bottom: 0, left: -16 }}>
          <CartesianGrid strokeDasharray="4 4" stroke="var(--color-line)" vertical={false} />
          <XAxis dataKey="month" tick={{ fontSize: 12, fill: 'var(--color-muted)' }} tickLine={false} axisLine={false} />
          <YAxis allowDecimals={false} tick={{ fontSize: 12, fill: 'var(--color-muted)' }} tickLine={false} axisLine={false} />
          <Tooltip
            contentStyle={{
              borderRadius: 12,
              border: '1px solid var(--color-line)',
              boxShadow: 'var(--shadow-card)',
              fontFamily: 'var(--font-sans)',
              direction: 'rtl',
            }}
          />
          <Legend wrapperStyle={{ fontFamily: 'var(--font-sans)', fontSize: 13, direction: 'rtl' }} />
          {series.map((item) => (
            <Line
              key={item.key}
              type="monotone"
              dataKey={item.key}
              name={item.label}
              stroke={item.color}
              strokeWidth={2.5}
              dot={{ r: 3 }}
              activeDot={{ r: 5 }}
            />
          ))}
        </LineChart>
      </ResponsiveContainer>
    </div>
  );
}
