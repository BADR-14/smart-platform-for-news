import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { useState } from 'react';
import { Card } from '@/components/ui/Card';
import { DataTable } from '@/components/ui/DataTable';
import type { Column } from '@/components/ui/DataTable';
import { Select } from '@/components/ui/FormField';
import { FeaturedStatusBadge } from '@/features/shared/featured';
import { paymentMethodLabels } from '@/features/shared/labels';
import { compact } from '@/lib/hooks/useListParams';
import { formatDate, formatMoney } from '@/lib/utils/format';
import { adminApi } from '../api';
import type { FeaturedPost } from '../api';
import { adminKeys } from '../keys';

const columns: Column<FeaturedPost>[] = [
  { key: 'post', header: 'المنشور', className: 'max-w-72', cell: (row) => <p className="truncate font-medium text-ink">{row.post?.title ?? 'منشور محذوف'}</p> },
  { key: 'company', header: 'الشركة', cell: (row) => row.company?.name ?? '—' },
  { key: 'method', header: 'طريقة الدفع', cell: (row) => paymentMethodLabels[row.payment_method] },
  { key: 'cost', header: 'التكلفة', cell: (row) => <span className="ltr tabular-nums">{formatMoney(row.cost)}</span> },
  { key: 'status', header: 'الحالة', cell: (row) => <FeaturedStatusBadge status={row.status} /> },
  {
    key: 'period',
    header: 'الفترة',
    cell: (row) => (
      <span className="text-muted">
        {formatDate(row.starts_at)} ← {formatDate(row.ends_at)}
      </span>
    ),
  },
];

export function FeaturedPostsTable() {
  const [page, setPage] = useState(1);
  const [status, setStatus] = useState('');

  const params = compact({ page, status }) as { page: number; status?: FeaturedPost['status'] };

  const query = useQuery({
    queryKey: [...adminKeys.featured, params],
    queryFn: () => adminApi.featuredPosts(params),
    placeholderData: keepPreviousData,
  });

  return (
    <Card>
      <div className="flex flex-wrap items-center gap-3 border-b border-line p-4">
        <Select
          aria-label="الحالة"
          wrapperClassName="w-full sm:w-44"
          value={status}
          onChange={(event) => {
            setStatus(event.target.value);
            setPage(1);
          }}
        >
          <option value="">كل الحالات</option>
          <option value="active">ساري</option>
          <option value="expired">منتهٍ</option>
        </Select>
      </div>

      <DataTable
        columns={columns}
        rows={query.data?.data}
        rowKey={(row) => row.id}
        isLoading={query.isLoading}
        isFetching={query.isFetching}
        error={query.error}
        onRetry={() => query.refetch()}
        meta={query.data?.meta}
        onPageChange={setPage}
        emptyTitle="لا توجد منشورات مميزة"
      />
    </Card>
  );
}
