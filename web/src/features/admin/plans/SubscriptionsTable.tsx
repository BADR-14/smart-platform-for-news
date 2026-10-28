import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { useState } from 'react';
import { Badge } from '@/components/ui/Badge';
import { Card } from '@/components/ui/Card';
import { DataTable } from '@/components/ui/DataTable';
import type { Column } from '@/components/ui/DataTable';
import { Select } from '@/components/ui/FormField';
import { SearchInput } from '@/components/ui/SearchInput';
import { compact } from '@/lib/hooks/useListParams';
import { formatDate, formatMoney } from '@/lib/utils/format';
import { adminApi } from '../api';
import type { Subscription } from '../api';
import { adminKeys } from '../keys';

const columns: Column<Subscription>[] = [
  { key: 'company', header: 'الشركة', cell: (row) => <span className="font-medium text-ink">{row.company_name}</span> },
  { key: 'plan', header: 'الباقة', cell: (row) => row.plan_name },
  {
    key: 'remaining',
    header: 'المنشورات المتبقية',
    align: 'center',
    cell: (row) => (
      <span className="ltr tabular-nums">
        {row.posts_remaining} / {row.posts_count}
      </span>
    ),
  },
  { key: 'paid', header: 'المدفوع', cell: (row) => <span className="ltr tabular-nums">{formatMoney(row.price_paid)}</span> },
  {
    key: 'status',
    header: 'الحالة',
    cell: (row) => (row.status === 'active' ? <Badge tone="success" dot>نشط</Badge> : <Badge tone="neutral" dot>منتهٍ</Badge>),
  },
  { key: 'date', header: 'تاريخ الشراء', cell: (row) => <span className="text-muted">{formatDate(row.purchased_at)}</span> },
];

export function SubscriptionsTable() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');

  const params = compact({ page, search, status }) as { page: number; search?: string; status?: 'active' | 'expired' };

  const query = useQuery({
    queryKey: [...adminKeys.subscriptions, params],
    queryFn: () => adminApi.subscriptions(params),
    placeholderData: keepPreviousData,
  });

  return (
    <Card>
      <div className="flex flex-wrap items-center gap-3 border-b border-line p-4">
        <SearchInput
          value={search}
          placeholder="بحث باسم الشركة…"
          onChange={(value) => {
            setSearch(value);
            setPage(1);
          }}
        />
        <Select
          aria-label="الحالة"
          wrapperClassName="w-full sm:w-40"
          value={status}
          onChange={(event) => {
            setStatus(event.target.value);
            setPage(1);
          }}
        >
          <option value="">كل الحالات</option>
          <option value="active">نشط</option>
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
        emptyTitle="لا توجد اشتراكات"
      />
    </Card>
  );
}
