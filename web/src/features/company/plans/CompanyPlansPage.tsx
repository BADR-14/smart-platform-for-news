import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { Check, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardHeader } from '@/components/ui/Card';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { DataTable } from '@/components/ui/DataTable';
import type { Column } from '@/components/ui/DataTable';
import { PageHeader } from '@/components/ui/PageHeader';
import { EmptyState, ErrorState, LoadingState } from '@/components/ui/States';
import { useActionMutation } from '@/lib/hooks/useActionMutation';
import { cn } from '@/lib/utils/cn';
import { formatDate, formatMoney } from '@/lib/utils/format';
import { companyApi } from '../api';
import type { CompanyPlan, CompanySubscription } from '../api';
import { billingKeys, companyKeys } from '../keys';

const historyColumns: Column<CompanySubscription>[] = [
  { key: 'plan', header: 'الباقة', cell: (row) => <span className="font-medium text-ink">{row.plan_name}</span> },
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
    cell: (row) => (row.status === 'active' ? <Badge tone="success" dot>نشطة</Badge> : <Badge tone="neutral" dot>منتهية</Badge>),
  },
  { key: 'date', header: 'تاريخ الشراء', cell: (row) => <span className="text-muted">{formatDate(row.purchased_at)}</span> },
];

export function CompanyPlansPage() {
  const [buying, setBuying] = useState<CompanyPlan | null>(null);
  const [page, setPage] = useState(1);

  const plans = useQuery({ queryKey: companyKeys.plans, queryFn: companyApi.plans });
  const wallet = useQuery({ queryKey: companyKeys.wallet, queryFn: companyApi.wallet });
  const history = useQuery({
    queryKey: [...companyKeys.subscriptions, page],
    queryFn: () => companyApi.subscriptions({ page }),
    placeholderData: keepPreviousData,
  });

  const subscribe = useActionMutation({
    mutationFn: companyApi.subscribe,
    invalidate: billingKeys,
    onSuccess: () => setBuying(null),
  });

  if (plans.error) return <ErrorState error={plans.error} onRetry={() => plans.refetch()} />;
  if (plans.isLoading || !plans.data) return <LoadingState />;

  const { active_plan: activePlan, single_post_price: singlePrice, featured_duration_days: days, currency } = plans.data;
  const balance = wallet.data?.wallet.balance ?? 0;

  return (
    <>
      <PageHeader
        title="الباقات"
        description={`تمييز المنشور الواحد من المحفظة يكلّف ${formatMoney(singlePrice, currency)} لمدة ${days} أيام. الباقات أوفر.`}
      />

      {activePlan ? (
        <Alert tone="success" title={`باقتك الحالية: ${activePlan.plan_name}`} className="mb-6">
          متبقٍ <strong>{activePlan.posts_remaining}</strong> من أصل {activePlan.posts_count} منشور مميز. يمكنك شراء باقة جديدة بعد استهلاك الباقة الحالية.
        </Alert>
      ) : (
        <Alert tone="info" className="mb-6">
          رصيد محفظتك <strong className="ltr">{formatMoney(balance, currency)}</strong>.{' '}
          <Link to="/company/wallet" className="font-semibold underline">
            اشحن المحفظة
          </Link>{' '}
          إذا لم يكفِ لشراء الباقة.
        </Alert>
      )}

      {plans.data.plans.length === 0 ? (
        <Card>
          <EmptyState title="لا توجد باقات متاحة حالياً" description="ستظهر هنا الباقات عندما تضيفها الإدارة." />
        </Card>
      ) : (
        <section className="mb-8 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
          {plans.data.plans.map((plan, index) => {
            const saving = plan.price_per_post !== null && plan.price_per_post < singlePrice ? Math.round((1 - plan.price_per_post / singlePrice) * 100) : 0;
            const highlighted = index === 1 && plans.data.plans.length > 2;

            return (
              <article
                key={plan.id}
                className={cn(
                  'relative flex flex-col rounded-(--radius-card) border bg-surface p-6 shadow-(--shadow-card)',
                  highlighted ? 'border-accent-500 ring-1 ring-accent-500' : 'border-line',
                )}
              >
                {highlighted && (
                  <span className="absolute -top-3 start-6 rounded-full bg-accent-500 px-3 py-0.5 text-xs font-semibold text-white">الأكثر طلباً</span>
                )}

                <h2 className="text-lg font-bold text-ink">{plan.name}</h2>
                <p className="ltr mt-3 text-end text-3xl font-bold tabular-nums text-brand-800">{formatMoney(plan.price, currency)}</p>

                <ul className="mt-5 flex-1 space-y-2.5 text-sm text-ink-soft">
                  <li className="flex items-center gap-2">
                    <Check className="size-4 shrink-0 text-success-600" />
                    <strong>{plan.posts_count}</strong> منشور مميز
                  </li>
                  <li className="flex items-center gap-2">
                    <Check className="size-4 shrink-0 text-success-600" />
                    <span>
                      <span className="ltr tabular-nums">{formatMoney(plan.price_per_post, currency)}</span> للمنشور الواحد
                    </span>
                  </li>
                  <li className="flex items-center gap-2">
                    <Check className="size-4 shrink-0 text-success-600" />
                    كل منشور مميز لمدة {days} أيام
                  </li>
                </ul>

                {saving > 0 && (
                  <Badge tone="success" className="mt-4 self-start">
                    توفير {saving}% عن السعر المفرد
                  </Badge>
                )}

                <Button
                  className="mt-5"
                  variant={highlighted ? 'accent' : 'primary'}
                  icon={<Sparkles className="size-4" />}
                  disabled={activePlan !== null}
                  onClick={() => setBuying(plan)}
                >
                  {activePlan ? 'لديك باقة نشطة' : 'اشترك الآن'}
                </Button>
              </article>
            );
          })}
        </section>
      )}

      <Card>
        <CardHeader title="سجل الاشتراكات" />
        <DataTable
          columns={historyColumns}
          rows={history.data?.data}
          rowKey={(row) => row.id}
          isLoading={history.isLoading}
          isFetching={history.isFetching}
          error={history.error}
          onRetry={() => history.refetch()}
          meta={history.data?.meta}
          onPageChange={setPage}
          emptyTitle="لم تشترك بأي باقة بعد"
        />
      </Card>

      <ConfirmDialog
        open={buying !== null}
        onClose={() => setBuying(null)}
        tone="primary"
        title="تأكيد الاشتراك"
        message={
          buying && (
            <>
              سيُخصم <strong className="ltr">{formatMoney(buying.price, currency)}</strong> من محفظتك مقابل باقة «{buying.name}» ({buying.posts_count} منشور
              مميز).
              {balance < buying.price && (
                <span className="mt-2 block text-danger-600">
                  رصيدك الحالي {formatMoney(balance, currency)} لا يكفي. اشحن المحفظة أولاً.
                </span>
              )}
            </>
          )
        }
        confirmLabel="اشترك"
        loading={subscribe.isPending}
        onConfirm={() => buying && subscribe.mutate(buying.id)}
      />
    </>
  );
}
