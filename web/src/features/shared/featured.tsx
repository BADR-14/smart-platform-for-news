import { Badge } from '@/components/ui/Badge';
import type { BadgeTone } from '@/components/ui/Badge';

type FeaturedStatus = 'queued' | 'active' | 'expired' | 'cancelled';

const meta: Record<FeaturedStatus, { label: string; tone: BadgeTone }> = {
  active: { label: 'ساري', tone: 'success' },
  queued: { label: 'في الانتظار', tone: 'warning' },
  expired: { label: 'منتهٍ', tone: 'neutral' },
  cancelled: { label: 'ملغى', tone: 'danger' },
};

export function FeaturedStatusBadge({ status }: { status: FeaturedStatus }) {
  return (
    <Badge tone={meta[status].tone} dot>
      {meta[status].label}
    </Badge>
  );
}
