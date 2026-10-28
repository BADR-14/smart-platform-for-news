import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { ArrowDownLeft, ArrowUpRight, Banknote, Plus, TrendingDown, TrendingUp, Wallet as WalletIcon } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardHeader } from '@/components/ui/Card';
import { DataTable } from '@/components/ui/DataTable';
import type { Column } from '@/components/ui/DataTable';
import { Tabs } from '@/components/ui/Misc';
import { PageHeader } from '@/components/ui/PageHeader';
import { StatCard } from '@/components/ui/StatCard';
import { ErrorState } from '@/components/ui/States';
import { compact } from '@/lib/hooks/useListParams';
import { cn } from '@/lib/utils/cn';
import { formatDateTime, formatMoney } from '@/lib/utils/format';
import { companyApi } from '../api';
import type { Transaction, TransactionKind } from '../api';
import { companyKeys } from '../keys';
import { TopUpDialog } from './TopUpDialog';
import { WithdrawDialog } from './WithdrawDialog';

const kindLabels: Record<TransactionKind, string> = {
  topup: 'شحن المحفظة',
  withdrawal: 'سحب',
  plan_purchase: 'شراء باقة',
  featured_post: 'تمييز منشور',
};

type TypeFilter = '' | 'credit' | 'debit';

const columns: Column<Transaction>[] = [
  {
    key: 'kind',
    header: 'العملية',
    cell: (row) => {
      const incoming = row.type !== 'debit';

      return (
        <div className="flex items-center gap-3">
          <span
            className={cn(
              'flex size-9 shrink-0 items-center justify-center rounded-xl',
              incoming ? 'bg-success-50 text-success-600' : 'bg-danger-50 text-danger-600',
            )}
          >
            {incoming ? <ArrowDownLeft className="size-4" /> : <ArrowUpRight className="size-4" />}
          </span>
          <div className="min-w-0">
            <p className="font-medium text-ink">{row.kind ? kindLabels[row.kind] : incoming ? 'إيداع' : 'خصم'}</p>
            <p className="truncate text-xs text-muted">{row.description}</p>
          </div>
        </div>
      );
    },
  },
  {
    key: 'amount',
    header: 'المبلغ',
    cell: (row) => (
      <span className={cn('ltr font-semibold tabular-nums', row.type === 'debit' ? 'text-danger-600' : 'text-success-600')}>
        {row.type === 'debit' ? '−' : '+'}
        {formatMoney(row.amount, row.currency)}
      </span>
    ),
  },
  {
    key: 'status',
    header: 'الحالة',
    cell: (row) =>
      row.status === 'completed' ? <Badge tone="success">مكتملة</Badge> : row.status === 'pending' ? <Badge tone="warning">معلّقة</Badge> : <Badge tone="danger">فشلت</Badge>,
  },
  { key: 'date', header: 'التاريخ', cell: (row) => <span className="text-muted">{formatDateTime(row.created_at)}</span> },
];

export function WalletPage() {
  const [dialog, setDialog] = useState<'topup' | 'withdraw' | null>(null);
  const [page, setPage] = useState(1);
  const [type, setType] = useState<TypeFilter>('');

  const wallet = useQuery({ queryKey: companyKeys.wallet, queryFn: companyApi.wallet });

  const params = compact({ page, type }) as { page: number; type?: Transaction['type'] };
  const transactions = useQuery({
    queryKey: [...companyKeys.transactions, params],
    queryFn: () => companyApi.transactions(params),
    placeholderData: keepPreviousData,
  });

  if (wallet.error) return <ErrorState error={wallet.error} onRetry={() => wallet.refetch()} />;

  const data = wallet.data;
  const currency = data?.wallet.currency ?? 'USD';

  return (
    <>
      <PageHeader
        title="المحفظة"
        description="اشحن رصيدك لشراء الباقات وتمييز المنشورات."
        actions={
          <>
            <Button variant="outline" icon={<Banknote className="size-4" />} onClick={() => setDialog('withdraw')} disabled={!data || data.wallet.balance <= 0}>
              سحب
            </Button>
            <Button variant="accent" icon={<Plus className="size-4" />} onClick={() => setDialog('topup')} disabled={!data}>
              شحن المحفظة
            </Button>
          </>
        }
      />

      <section className="mb-6 grid gap-4 sm:grid-cols-3">
        <StatCard label="الرصيد الحالي" value={formatMoney(data?.wallet.balance, currency)} icon={<WalletIcon className="size-5" />} tone="success" loading={wallet.isLoading} />
        <StatCard label="إجمالي الإيداعات" value={formatMoney(data?.summary.total_credited, currency)} icon={<TrendingUp className="size-5" />} tone="info" loading={wallet.isLoading} />
        <StatCard label="إجمالي المصروفات" value={formatMoney(data?.summary.total_debited, currency)} icon={<TrendingDown className="size-5" />} tone="accent" loading={wallet.isLoading} />
      </section>

      <Card>
        <CardHeader
          title="سجل المعاملات"
          action={
            <Tabs<TypeFilter>
              value={type}
              onChange={(value) => {
                setType(value);
                setPage(1);
              }}
              items={[
                { value: '', label: 'الكل' },
                { value: 'credit', label: 'إيداعات' },
                { value: 'debit', label: 'مصروفات' },
              ]}
            />
          }
        />
        <DataTable
          columns={columns}
          rows={transactions.data?.data}
          rowKey={(row) => row.uuid}
          isLoading={transactions.isLoading}
          isFetching={transactions.isFetching}
          error={transactions.error}
          onRetry={() => transactions.refetch()}
          meta={transactions.data?.meta}
          onPageChange={setPage}
          emptyTitle="لا توجد معاملات"
          emptyDescription="ستظهر هنا عمليات الشحن والشراء."
        />
      </Card>

      {dialog === 'topup' && <TopUpDialog currency={currency} onClose={() => setDialog(null)} />}
      {dialog === 'withdraw' && data && <WithdrawDialog balance={data.wallet.balance} currency={currency} onClose={() => setDialog(null)} />}
    </>
  );
}
