import { Pencil, Power, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { DataTable } from '@/components/ui/DataTable';
import type { Column } from '@/components/ui/DataTable';
import { useActionMutation } from '@/lib/hooks/useActionMutation';
import { formatMoney, formatNumber } from '@/lib/utils/format';
import { adminApi } from '../api';
import type { Plan } from '../api';
import { adminKeys } from '../keys';

interface PlansTableProps {
  plans: Plan[] | undefined;
  isLoading: boolean;
  singlePostPrice: number | undefined;
  onEdit: (plan: Plan) => void;
}

export function PlansTable({ plans, isLoading, singlePostPrice, onEdit }: PlansTableProps) {
  const [deleting, setDeleting] = useState<Plan | null>(null);

  const toggle = useActionMutation({ mutationFn: adminApi.togglePlan, invalidate: [adminKeys.plans] });
  const remove = useActionMutation({
    mutationFn: adminApi.deletePlan,
    invalidate: [adminKeys.plans],
    onSuccess: () => setDeleting(null),
  });

  const columns: Column<Plan>[] = [
    { key: 'name', header: 'الباقة', cell: (plan) => <span className="font-medium text-ink">{plan.name}</span> },
    { key: 'posts', header: 'منشورات مميزة', align: 'center', cell: (plan) => formatNumber(plan.posts_count) },
    { key: 'price', header: 'السعر', cell: (plan) => <span className="ltr font-medium tabular-nums">{formatMoney(plan.price)}</span> },
    {
      key: 'per_post',
      header: 'سعر المنشور ضمن الباقة',
      cell: (plan) => {
        const saving =
          singlePostPrice && plan.price_per_post !== null && plan.price_per_post < singlePostPrice
            ? Math.round((1 - plan.price_per_post / singlePostPrice) * 100)
            : 0;

        return (
          <div className="flex items-center gap-2">
            <span className="ltr tabular-nums">{formatMoney(plan.price_per_post)}</span>
            {saving > 0 && <Badge tone="success">توفير {saving}%</Badge>}
          </div>
        );
      },
    },
    { key: 'subs', header: 'الاشتراكات', align: 'center', cell: (plan) => formatNumber(plan.subscriptions_count) },
    {
      key: 'status',
      header: 'الحالة',
      cell: (plan) => (plan.is_active ? <Badge tone="success" dot>مفعّلة</Badge> : <Badge tone="neutral" dot>معطّلة</Badge>),
    },
    {
      key: 'actions',
      header: 'إجراءات',
      align: 'end',
      cell: (plan) => (
        <div className="flex justify-end gap-1">
          <Button variant="ghost" size="icon" title="تعديل" aria-label="تعديل الباقة" onClick={() => onEdit(plan)}>
            <Pencil className="size-4" />
          </Button>
          <Button
            variant="ghost"
            size="icon"
            title={plan.is_active ? 'تعطيل' : 'تفعيل'}
            aria-label={plan.is_active ? 'تعطيل الباقة' : 'تفعيل الباقة'}
            disabled={toggle.isPending}
            onClick={() => toggle.mutate(plan.id)}
          >
            <Power className={plan.is_active ? 'size-4 text-success-600' : 'size-4 text-muted'} />
          </Button>
          <Button variant="ghost" size="icon" title="حذف" aria-label="حذف الباقة" onClick={() => setDeleting(plan)}>
            <Trash2 className="size-4 text-danger-600" />
          </Button>
        </div>
      ),
    },
  ];

  return (
    <Card className="h-fit">
      <DataTable
        columns={columns}
        rows={plans}
        rowKey={(plan) => plan.id}
        isLoading={isLoading}
        emptyTitle="لا توجد باقات"
        emptyDescription="أضف أول باقة لتتمكن الشركات من شراء منشورات مميزة بسعر أوفر."
      />

      <ConfirmDialog
        open={deleting !== null}
        onClose={() => setDeleting(null)}
        title="حذف الباقة"
        message={
          <>
            هل تريد حذف <strong>{deleting?.name}</strong>؟ الباقة التي عليها اشتراكات لا تُحذف ويمكن تعطيلها بدلاً من ذلك.
          </>
        }
        confirmLabel="حذف"
        loading={remove.isPending}
        onConfirm={() => deleting && remove.mutate(deleting.id)}
      />
    </Card>
  );
}
