interface SparklineProps {
  values: number[];
  color?: string;
  width?: number;
  height?: number;
}

/** خط اتجاه صغير بلا محاور، يُرسم بحركة عند الظهور. */
export function Sparkline({ values, color = 'currentColor', width = 120, height = 36 }: SparklineProps) {
  if (values.length < 2) return <div style={{ width, height }} />;

  const max = Math.max(...values);
  const min = Math.min(...values);
  const range = max - min || 1;
  const pad = 3;

  const points = values.map((value, index) => {
    const x = pad + (index / (values.length - 1)) * (width - pad * 2);
    const y = height - pad - ((value - min) / range) * (height - pad * 2);

    return `${x.toFixed(1)},${y.toFixed(1)}`;
  });

  const [lastX, lastY] = points[points.length - 1].split(',');

  return (
    // الزمن يسير من اليسار لليمين مهما كان اتجاه الصفحة
    <svg className="ltr" width={width} height={height} viewBox={`0 0 ${width} ${height}`} aria-hidden>
      <polyline
        points={points.join(' ')}
        fill="none"
        stroke={color}
        strokeWidth={2}
        strokeLinecap="round"
        strokeLinejoin="round"
        pathLength={100}
        strokeDasharray={100}
        className="animate-draw"
      />
      <circle cx={lastX} cy={lastY} r={3} fill={color} />
    </svg>
  );
}
