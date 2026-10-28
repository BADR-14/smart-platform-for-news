import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { Sparkles } from 'lucide-react';
import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { DataTable } from '@/components/ui/DataTable';
import type { Column } from '@/components/ui/DataTable';
import { PageHeader } from '@/components/ui/PageHeader';
import { FeaturedStatusBadge } from '@/features/shared/featured';
import { paymentMethodLabels } from '@/features/shared/labels';
import { formatDate, formatMoney } from '@/lib/utils/format';
import { companyApi } from '../api';
import type { CompanyFeaturedPost } from '../api';
import { companyKeys } from '../keys';

const columns: Column<CompanyFeaturedPost>[] = [
  {
    key: 'post',
    header: 'المنشور',
    className: 'max-w-80',
    cell: (row) =>
      row.post ? (
        <Link to={`/company/posts/${row.post.id}/edit`} className="block truncate font-medium text-ink hover:text-brand-600">
          {row.post.title}
        </Link>
      ) : (
        <span className="text-muted">منشور محذوف</span>
      ),
  },
  { key: 'method', header: 'طريقة الدفع', cell: (row) => paymentMethodLabels[row.payment_method] },
  { key: 'cost', header: 'التكلفة', cell: (row) => <span className="ltr tabular-nums">{formatMoney(row.cost)}</span> },
  { key: 'status', header: 'الحالة', cell: (row) => <FeaturedStatusBadge status={row.status} /> },
  { key: 'start', header: 'بداية التمييز', cell: (row) => <span className="text-muted">{formatDate(row.starts_at)}</span> },
  { key: 'end', header: 'نهاية التمييز', cell: (row) => <span className="text-muted">{formatDate(row.ends_at)}</span> },
];

export function FeaturedPage() {
  const [page, setPage] = useState(1);

  const options = useQuery({ queryKey: companyKeys.featureOptions, queryFn: companyApi.featureOptions });
  const query = useQuery({
    queryKey: [...companyKeys.featured, page],
    queryFn: () => companyApi.featuredPosts({ page }),
    placeholderData: keepPreviousData,
  });

  return (
    <>
      <PageHeader
        title="المنشورات المميزة"
        description="المنشور المميز يظهر أعلى الصفحة الرئيسية لكل المستخدمين."
        actions={
          <Link to="/company/posts?status=published">
            <Button variant="accent" icon={<Sparkles className="size-4" />}>
              تمييز منشور
            </Button>
          </Link>
        }
      />

      {options.data && (
        <Alert tone="info" className="mb-6">
          سعر تمييز المنشور الواحد من المحفظة <strong className="ltr">{formatMoney(options.data.price, options.data.currency)}</strong> لمدة{' '}
          <strong>{options.data.duration_days} أيام</strong>.{' '}
          {options.data.active_plan ? (
            <>
              لديك <strong>{options.data.active_plan.posts_remaining}</strong> منشور متبقٍ في باقة «{options.data.active_plan.name}».
            </>
          ) : (
            <>
              <Link to="/company/plans" className="font-semibold underline">
                اشترك بباقة
              </Link>{' '}
              لتحصل على سعر أوفر للمنشور.
            </>
          )}
        </Alert>
      )}

      <Card>
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
          emptyTitle="لم تميّز أي منشور بعد"
          emptyDescription="اذهب إلى منشوراتك المنشورة واضغط «تمييز»."
        />
      </Card>
    </>
  );
}
