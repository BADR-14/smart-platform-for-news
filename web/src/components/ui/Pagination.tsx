import { ChevronLeft, ChevronRight } from 'lucide-react';
import type { PaginationMeta } from '@/lib/api/types';
import { formatNumber } from '@/lib/utils/format';
import { Button } from './Button';

interface PaginationProps {
  meta: PaginationMeta;
  onPageChange: (page: number) => void;
}

export function Pagination({ meta, onPageChange }: PaginationProps) {
  const { current_page: page, last_page: lastPage, total } = meta;

  return (
    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-3 text-sm text-muted">
      <p>
        الإجمالي: <span className="font-semibold text-ink">{formatNumber(total)}</span>
      </p>

      <div className="flex items-center gap-2">
        {/* في الواجهة العربية "السابق" على اليمين وسهمه يشير لليمين */}
        <Button
          variant="outline"
          size="sm"
          disabled={page <= 1}
          onClick={() => onPageChange(page - 1)}
          icon={<ChevronRight className="size-4" />}
        >
          السابق
        </Button>
        <span className="min-w-20 text-center">
          صفحة <span className="font-semibold text-ink">{page}</span> من {lastPage}
        </span>
        <Button variant="outline" size="sm" disabled={page >= lastPage} onClick={() => onPageChange(page + 1)}>
          التالي
          <ChevronLeft className="size-4" />
        </Button>
      </div>
    </div>
  );
}
