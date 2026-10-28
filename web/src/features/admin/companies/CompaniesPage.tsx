import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { Ban, Building2, ShieldCheck, ShieldX, Wallet } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { DataTable } from '@/components/ui/DataTable';
import type { Column } from '@/components/ui/DataTable';
import { Select } from '@/components/ui/FormField';
import { Avatar } from '@/components/ui/Misc';
import { PageHeader } from '@/components/ui/PageHeader';
import { ReasonDialog } from '@/components/ui/ReasonDialog';
import { SearchInput } from '@/components/ui/SearchInput';
import { StatCard } from '@/components/ui/StatCard';
import { useCategories } from '@/features/shared/categories';
import { useActionMutation } from '@/lib/hooks/useActionMutation';
import { compact, useListParams } from '@/lib/hooks/useListParams';
import { formatMoney, formatNumber } from '@/lib/utils/format';
import { adminApi } from '../api';
import type { AdminCompany, CompanyFilters } from '../api';
import { adminKeys } from '../keys';

type Dialog = { type: 'ban' | 'unban'; company: AdminCompany } | null;

export function CompaniesPage() {
  const { page, search, filters, setPage, setSearch, setFilter } = useListParams(['status', 'category_id'] as const);
  const [dialog, setDialog] = useState<Dialog>(null);
  const categories = useCategories('company');

  const params = compact({ page, search, ...filters }) as CompanyFilters;

  const query = useQuery({
    queryKey: [...adminKeys.companies, params],
    queryFn: () => adminApi.companies(params),
    placeholderData: keepPreviousData,
  });

  const close = () => setDialog(null);
  const invalidate = [adminKeys.companies, adminKeys.overview];

  const ban = useActionMutation({
    mutationFn: ({ id, reason }: { id: number; reason: string }) => adminApi.banCompany(id, reason),
    invalidate,
    onSuccess: close,
  });
  const unban = useActionMutation({ mutationFn: adminApi.unbanCompany, invalidate, onSuccess: close });

  const columns: Column<AdminCompany>[] = [
    {
      key: 'company',
      header: 'الشركة',
      cell: (company) => (
        <div className="flex items-center gap-3">
          <Avatar name={company.name} src={company.logo} size="sm" />
          <div className="min-w-0">
            <p className="truncate font-medium text-ink">{company.name}</p>
            <p className="ltr truncate text-end text-xs text-muted">{company.email}</p>
          </div>
        </div>
      ),
    },
    { key: 'category', header: 'التصنيف', cell: (company) => company.category?.name ?? '—' },
    {
      key: 'wallet',
      header: 'رصيد المحفظة',
      cell: (company) => <span className="ltr font-medium tabular-nums">{formatMoney(company.wallet_balance)}</span>,
    },
    {
      key: 'plan',
      header: 'الباقة',
      cell: (company) =>
        company.active_plan ? (
          <Badge tone="accent">
            {company.active_plan.name} · متبقٍ {company.active_plan.posts_remaining}
          </Badge>
        ) : (
          <span className="text-muted">—</span>
        ),
    },
    { key: 'posts', header: 'المنشورات', align: 'center', cell: (company) => formatNumber(company.posts_count) },
    { key: 'followers', header: 'المتابعون', align: 'center', cell: (company) => formatNumber(company.followers_count) },
    {
      key: 'status',
      header: 'الحالة',
      cell: (company) =>
        company.is_active ? (
          <Badge tone="success" dot>
            نشطة
          </Badge>
        ) : (
          <span title={company.ban_reason ?? undefined}>
            <Badge tone="danger" dot>
              محظورة
            </Badge>
          </span>
        ),
    },
    {
      key: 'actions',
      header: 'إجراءات',
      align: 'end',
      cell: (company) =>
        company.is_active ? (
          <Button variant="danger-outline" size="sm" icon={<Ban className="size-3.5" />} onClick={() => setDialog({ type: 'ban', company })}>
            حظر
          </Button>
        ) : (
          <Button variant="outline" size="sm" icon={<ShieldCheck className="size-3.5" />} onClick={() => setDialog({ type: 'unban', company })}>
            إلغاء الحظر
          </Button>
        ),
    },
  ];

  const stats = query.data?.stats;

  return (
    <>
      <PageHeader title="الشركات" description="الشركات الموافق عليها. طلبات الشركات الجديدة في صفحة طلبات التوثيق." />

      <section className="mb-6 grid gap-4 sm:grid-cols-3">
        <StatCard label="إجمالي الشركات" value={formatNumber(stats?.total_companies)} icon={<Building2 className="size-5" />} loading={query.isLoading} />
        <StatCard label="شركات محظورة" value={formatNumber(stats?.blocked_companies)} icon={<ShieldX className="size-5" />} tone="danger" loading={query.isLoading} />
        <StatCard label="إجمالي أرصدة المحافظ" value={formatMoney(stats?.total_balance)} icon={<Wallet className="size-5" />} tone="success" loading={query.isLoading} />
      </section>

      <Card>
        <div className="flex flex-wrap items-center gap-3 border-b border-line p-4">
          <SearchInput value={search} onChange={setSearch} placeholder="بحث باسم الشركة…" />
          <Select
            aria-label="التصنيف"
            wrapperClassName="w-full sm:w-48"
            value={filters.category_id}
            onChange={(event) => setFilter('category_id', event.target.value)}
          >
            <option value="">كل التصنيفات</option>
            {categories.data?.map((category) => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </Select>
          <Select
            aria-label="الحالة"
            wrapperClassName="w-full sm:w-40"
            value={filters.status}
            onChange={(event) => setFilter('status', event.target.value)}
          >
            <option value="">كل الحالات</option>
            <option value="active">نشطة</option>
            <option value="blocked">محظورة</option>
          </Select>
        </div>

        <DataTable
          columns={columns}
          rows={query.data?.data}
          rowKey={(company) => company.id}
          isLoading={query.isLoading}
          isFetching={query.isFetching}
          error={query.error}
          onRetry={() => query.refetch()}
          meta={query.data?.meta}
          onPageChange={setPage}
          emptyTitle="لا توجد شركات"
        />
      </Card>

      <ReasonDialog
        open={dialog?.type === 'ban'}
        onClose={close}
        title="حظر الشركة"
        description={
          <>
            ستُحظر <strong>{dialog?.company.name}</strong> وتختفي من قائمة الشركات، وتُنهى جلساتها.
          </>
        }
        label="سبب الحظر"
        confirmLabel="حظر"
        loading={ban.isPending}
        onSubmit={(reason) => dialog && ban.mutate({ id: dialog.company.id, reason })}
      />

      <ConfirmDialog
        open={dialog?.type === 'unban'}
        onClose={close}
        tone="primary"
        title="إلغاء الحظر"
        message={
          <>
            هل تريد إلغاء حظر <strong>{dialog?.company.name}</strong>؟
          </>
        }
        confirmLabel="إلغاء الحظر"
        loading={unban.isPending}
        onConfirm={() => dialog && unban.mutate(dialog.company.id)}
      />
    </>
  );
}
