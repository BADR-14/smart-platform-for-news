import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { Building2, Flag, Newspaper, Users } from 'lucide-react';
import { toChartRows } from '@/components/charts/chart-utils';
import { GrowthChart } from '@/components/charts/GrowthChart';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { DataTable } from '@/components/ui/DataTable';
import type { Column } from '@/components/ui/DataTable';
import { Select } from '@/components/ui/FormField';
import { PageHeader } from '@/components/ui/PageHeader';
import { StatCard } from '@/components/ui/StatCard';
import { ErrorState, LoadingState } from '@/components/ui/States';
import { useListParams } from '@/lib/hooks/useListParams';
import { formatNumber } from '@/lib/utils/format';
import { adminApi } from '../api';
import type { MonthlyRecord } from '../api';
import { adminKeys } from '../keys';

const months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

const columns: Column<MonthlyRecord>[] = [
  { key: 'month', header: 'الشهر', cell: (row) => <span className="font-medium text-ink">{row.month_name} {row.year}</span> },
  { key: 'users', header: 'مستخدمون جدد', align: 'center', cell: (row) => formatNumber(row.users_count) },
  { key: 'companies', header: 'شركات جديدة', align: 'center', cell: (row) => formatNumber(row.companies_count) },
  { key: 'news', header: 'منشورات جديدة', align: 'center', cell: (row) => formatNumber(row.news_count) },
  { key: 'reports', header: 'بلاغات', align: 'center', cell: (row) => formatNumber(row.reports_count) },
];

export function AnalyticsPage() {
  const { page, filters, setPage, setFilter } = useListParams(['year', 'month'] as const);

  const params = {
    page,
    year: filters.year ? Number(filters.year) : undefined,
    month: filters.month ? Number(filters.month) : undefined,
  };

  const query = useQuery({
    queryKey: [...adminKeys.analytics, params],
    queryFn: () => adminApi.analytics(params),
    placeholderData: keepPreviousData,
  });

  if (query.error) return <ErrorState error={query.error} onRetry={() => query.refetch()} />;

  const data = query.data;
  const icon = 'size-5';

  return (
    <>
      <PageHeader title="التقارير" description="أرقام المنصة ونموها شهراً بشهر." />

      <section className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard label="المستخدمون" value={formatNumber(data?.counters.total_users)} icon={<Users className={icon} />} loading={query.isLoading} />
        <StatCard label="الشركات" value={formatNumber(data?.counters.total_companies)} icon={<Building2 className={icon} />} tone="accent" loading={query.isLoading} />
        <StatCard label="المنشورات" value={formatNumber(data?.counters.total_news)} icon={<Newspaper className={icon} />} tone="success" loading={query.isLoading} />
        <StatCard label="البلاغات" value={formatNumber(data?.counters.total_reports)} icon={<Flag className={icon} />} tone="danger" loading={query.isLoading} />
      </section>

      <Card className="mb-6">
        <CardHeader
          title={`النمو الشهري — ${data?.charts.year ?? ''}`}
          action={
            <Select aria-label="السنة" wrapperClassName="w-32" value={filters.year} onChange={(event) => setFilter('year', event.target.value)}>
              <option value="">السنة الحالية</option>
              {data?.available_years.map((year) => (
                <option key={year} value={year}>
                  {year}
                </option>
              ))}
            </Select>
          }
        />
        <CardBody>
          {!data ? (
            <LoadingState />
          ) : (
            <GrowthChart
              data={toChartRows(data.charts.labels, {
                users: data.charts.users_growth,
                news: data.charts.news_growth,
                companies: data.charts.companies_growth,
              })}
              series={[
                { key: 'users', label: 'المستخدمون', color: 'var(--color-brand-600)' },
                { key: 'news', label: 'المنشورات', color: 'var(--color-accent-500)' },
                { key: 'companies', label: 'الشركات', color: 'var(--color-success-600)' },
              ]}
            />
          )}
        </CardBody>
      </Card>

      <Card>
        <CardHeader
          title="السجل الشهري"
          description="الأشهر التي فيها نشاط، من الأحدث إلى الأقدم."
          action={
            <Select aria-label="الشهر" wrapperClassName="w-36" value={filters.month} onChange={(event) => setFilter('month', event.target.value)}>
              <option value="">كل الأشهر</option>
              {months.map((name, index) => (
                <option key={name} value={index + 1}>
                  {name}
                </option>
              ))}
            </Select>
          }
        />
        <DataTable
          columns={columns}
          rows={data?.monthly_reports.records}
          rowKey={(row) => `${row.year}-${row.month_number}`}
          isLoading={query.isLoading}
          isFetching={query.isFetching}
          meta={data?.monthly_reports.meta}
          onPageChange={setPage}
          emptyTitle="لا توجد بيانات لهذه الفترة"
        />
      </Card>
    </>
  );
}
