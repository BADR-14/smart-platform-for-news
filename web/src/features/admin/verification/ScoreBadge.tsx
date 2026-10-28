import { Badge } from '@/components/ui/Badge';

/** نتيجة القراءة الآلية (0–100). null = لم تُقرأ الوثائق آلياً (مراجعة يدوية). */
export function ScoreBadge({ score }: { score: number | null }) {
  if (score === null) {
    return <Badge tone="neutral">لم تُقرأ آلياً</Badge>;
  }

  const tone = score >= 90 ? 'success' : score >= 60 ? 'warning' : 'danger';

  return (
    <Badge tone={tone}>
      <span className="ltr tabular-nums">{score} / 100</span>
    </Badge>
  );
}
