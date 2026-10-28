import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { BadgeCheck, Building2, Hourglass, ShieldX, User } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/Badge';
import type { BadgeTone } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { DataTable } from '@/components/ui/DataTable';
import type { Column } from '@/components/ui/DataTable';
import { Select } from '@/components/ui/FormField';
import { Avatar, Tabs } from '@/components/ui/Misc';
import { PageHeader } from '@/components/ui/PageHeader';
import { SearchInput } from '@/components/ui/SearchInput';
import { StatCard } from '@/components/ui/StatCard';
import { compact, useListParams } from '@/lib/hooks/useListParams';
import { formatDateTime, formatNumber } from '@/lib/utils/format';
import { adminApi } from '../api';
import type { VerificationFilters, VerificationRequest, VerificationRequestStatus } from '../api';
import { adminKeys } from '../keys';
import { ScoreBadge } from './ScoreBadge';
import { VerificationReviewDialog } from './VerificationReviewDialog';

const statusMeta: Record<VerificationRequestStatus, { label: string; tone: BadgeTone }> = {
  pending: { label: 'بانتظار المراجعة', tone: 'warning' },
  approved: { label: 'موافق عليه', tone: 'success' },
  rejected: { label: 'مرفوض', tone: 'danger' },
};

type StatusTab = VerificationRequestStatus | '';

export function VerificationPage() {
  const { page, search, filters, setPage, setSearch, setFilter } = useListParams(['status', 'type'] as const);
  const [selectedId, setSelectedId] = useState<number | null>(null);

  const params = compact({ page, search, ...filters }) as VerificationFilters;

  const query = useQuery({
    queryKey: [...adminKeys.verification, params],
    queryFn: () => adminApi.verificationRequests(params),
    placeholderData: keepPreviousData,
  });

  // الطلب المفتوح يُقرأ من القائمة نفسها حتى يتحدّث مباشرة بعد المراجعة
  const selected = query.data?.data.find((request) => request.id === selectedId) ?? null;

  const columns: Column<VerificationRequest>[] = [
    {
      key: 'account',
      header: 'صاحب الطلب',
      cell: (request) => (
        <div className="flex items-center gap-3">
          <Avatar name={request.company?.name ?? request.account?.name ?? '?'} src={request.company?.logo ?? request.account?.avatar} size="sm" />
          <div className="min-w-0">
            <p className="truncate font-medium text-ink">{request.company?.name ?? request.account?.name}</p>
            <p className="ltr truncate text-end text-xs text-muted">{request.account?.email}</p>
          </div>
        </div>
      ),
    },
    {
      key: 'type',
      header: 'النوع',
      cell: (request) =>
        request.type === 'company' ? (
          <Badge tone="accent">
            <Building2 className="size-3" />
            شركة
          </Badge>
        ) : (
          <Badge tone="brand">
            <User className="size-3" />
            مستخدم
          </Badge>
        ),
    },
    { key: 'score', header: 'نتيجة القراءة الآلية', align: 'center', cell: (request) => <ScoreBadge score={request.confidence_score} /> },
    {
      key: 'status',
      header: 'الحالة',
      cell: (request) => <Badge tone={statusMeta[request.status].tone}>{statusMeta[request.status].label}</Badge>,
    },
    { key: 'submitted', header: 'تاريخ الطلب', cell: (request) => <span className="text-muted">{formatDateTime(request.submitted_at)}</span> },
    {
      key: 'actions',
      header: 'إجراءات',
      align: 'end',
      cell: (request) => (
        <Button size="sm" variant={request.status === 'pending' ? 'primary' : 'ghost'} onClick={() => setSelectedId(request.id)}>
          {request.status === 'pending' ? 'مراجعة' : 'التفاصيل'}
        </Button>
      ),
    },
  ];

  const stats = query.data?.stats;

  return (
    <>
      <PageHeader
        title="طلبات التوثيق"
        description="الطلبات التي نتيجتها بين 60 و 89 تصل إليك للمراجعة. الأعلى يُوافَق عليها والأدنى تُرفض آلياً."
      />

      <section className="mb-6 grid gap-4 sm:grid-cols-3">
        <StatCard label="بانتظار المراجعة" value={formatNumber(stats?.pending)} icon={<Hourglass className="size-5" />} tone="warning" loading={query.isLoading} />
        <StatCard label="موافق عليها" value={formatNumber(stats?.approved)} icon={<BadgeCheck className="size-5" />} tone="success" loading={query.isLoading} />
        <StatCard label="مرفوضة" value={formatNumber(stats?.rejected)} icon={<ShieldX className="size-5" />} tone="danger" loading={query.isLoading} />
      </section>

      <Tabs<StatusTab>
        className="mb-4"
        value={filters.status as StatusTab}
        onChange={(value) => setFilter('status', value)}
        items={[
          { value: '', label: 'الكل', count: stats?.total },
          { value: 'pending', label: 'بانتظار المراجعة', count: stats?.pending },
          { value: 'approved', label: 'موافق عليها', count: stats?.approved },
          { value: 'rejected', label: 'مرفوضة', count: stats?.rejected },
        ]}
      />

      <Card>
        <div className="flex flex-wrap items-center gap-3 border-b border-line p-4">
          <SearchInput value={search} onChange={setSearch} placeholder="بحث بالاسم أو البريد…" />
          <Select aria-label="نوع الطلب" wrapperClassName="w-full sm:w-44" value={filters.type} onChange={(event) => setFilter('type', event.target.value)}>
            <option value="">كل الأنواع</option>
            <option value="user">توثيق مستخدم</option>
            <option value="company">طلب شركة</option>
          </Select>
        </div>

        <DataTable
          columns={columns}
          rows={query.data?.data}
          rowKey={(request) => request.id}
          isLoading={query.isLoading}
          isFetching={query.isFetching}
          error={query.error}
          onRetry={() => query.refetch()}
          meta={query.data?.meta}
          onPageChange={setPage}
          emptyTitle="لا توجد طلبات توثيق"
        />
      </Card>

      {selected && <VerificationReviewDialog request={selected} onClose={() => setSelectedId(null)} />}
    </>
  );
}
