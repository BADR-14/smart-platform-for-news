import { useQuery } from '@tanstack/react-query';
import { CreditCard, Sparkles, Wallet } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Modal } from '@/components/ui/Modal';
import { ErrorState, LoadingState } from '@/components/ui/States';
import { useActionMutation } from '@/lib/hooks/useActionMutation';
import { cn } from '@/lib/utils/cn';
import { formatMoney } from '@/lib/utils/format';
import { companyApi } from '../api';
import type { FeaturePaymentMethod } from '../api';
import { billingKeys, companyKeys } from '../keys';

interface Props {
  post: { id: number; title: string };
  onClose: () => void;
}

/**
 * تمييز منشور: الشركة تختار الدفع من رصيد المحفظة مباشرة أو من رصيد الباقة (الأرخص).
 */
export function FeaturePostDialog({ post, onClose }: Props) {
  const options = useQuery({ queryKey: companyKeys.featureOptions, queryFn: companyApi.featureOptions });
  const [method, setMethod] = useState<FeaturePaymentMethod | null>(null);

  const feature = useActionMutation({
    mutationFn: (paymentMethod: FeaturePaymentMethod) => companyApi.featurePost(post.id, paymentMethod),
    invalidate: billingKeys,
    onSuccess: onClose,
  });

  const data = options.data;
  const canPayFromPlan = Boolean(data?.active_plan);
  const canPayFromWallet = data ? data.wallet_balance >= data.price : false;

  // الافتراضي: الباقة إن وُجدت (أرخص)، وإلا المحفظة
  const selected: FeaturePaymentMethod | null = method ?? (canPayFromPlan ? 'plan' : canPayFromWallet ? 'wallet' : null);

  return (
    <Modal
      open
      onClose={onClose}
      size="md"
      title="تمييز المنشور"
      description={post.title}
      footer={
        <>
          <Button variant="ghost" onClick={onClose} disabled={feature.isPending}>
            إلغاء
          </Button>
          <Button
            variant="accent"
            icon={<Sparkles className="size-4" />}
            disabled={!selected}
            loading={feature.isPending}
            onClick={() => selected && feature.mutate(selected)}
          >
            تمييز المنشور
          </Button>
        </>
      }
    >
      {options.isLoading ? (
        <LoadingState className="py-8" />
      ) : options.error || !data ? (
        <ErrorState error={options.error} onRetry={() => options.refetch()} className="py-8" />
      ) : (
        <div className="flex flex-col gap-4">
          <p className="text-sm leading-relaxed text-ink-soft">
            المنشور المميز يظهر أعلى الصفحة الرئيسية لمدة <strong>{data.duration_days} أيام</strong>. اختر طريقة الدفع:
          </p>

          <PaymentOption
            active={selected === 'plan'}
            disabled={!canPayFromPlan}
            onSelect={() => setMethod('plan')}
            icon={<CreditCard className="size-5" />}
            title="من رصيد الباقة"
            subtitle={
              data.active_plan
                ? `${data.active_plan.name} — متبقٍ ${data.active_plan.posts_remaining} منشور`
                : 'لا توجد باقة نشطة'
            }
            trailing={data.active_plan ? 'منشور واحد' : undefined}
          />

          <PaymentOption
            active={selected === 'wallet'}
            disabled={!canPayFromWallet}
            onSelect={() => setMethod('wallet')}
            icon={<Wallet className="size-5" />}
            title="من رصيد المحفظة"
            subtitle={`الرصيد الحالي: ${formatMoney(data.wallet_balance, data.currency)}`}
            trailing={formatMoney(data.price, data.currency)}
          />

          {!canPayFromPlan && !canPayFromWallet && (
            <Alert tone="warning" title="الرصيد غير كافٍ">
              <Link to="/company/wallet" className="font-semibold underline">
                اشحن محفظتك
              </Link>{' '}
              أو{' '}
              <Link to="/company/plans" className="font-semibold underline">
                اشترك بباقة
              </Link>{' '}
              لتتمكن من تمييز منشوراتك.
            </Alert>
          )}
        </div>
      )}
    </Modal>
  );
}

interface PaymentOptionProps {
  active: boolean;
  disabled: boolean;
  onSelect: () => void;
  icon: ReactNode;
  title: string;
  subtitle: string;
  trailing?: string;
}

function PaymentOption({ active, disabled, onSelect, icon, title, subtitle, trailing }: PaymentOptionProps) {
  return (
    <button
      type="button"
      role="radio"
      aria-checked={active}
      disabled={disabled}
      onClick={onSelect}
      className={cn(
        'flex items-center gap-4 rounded-xl border p-4 text-start transition-colors',
        active ? 'border-accent-500 bg-accent-50 ring-1 ring-accent-500' : 'border-line-strong hover:border-brand-300',
        disabled && 'cursor-not-allowed opacity-55 hover:border-line-strong',
      )}
    >
      <span className={cn('flex size-11 shrink-0 items-center justify-center rounded-xl', active ? 'bg-accent-500 text-white' : 'bg-brand-50 text-brand-600')}>
        {icon}
      </span>
      <span className="min-w-0 flex-1">
        <span className="block font-semibold text-ink">{title}</span>
        <span className="block text-sm text-muted">{subtitle}</span>
      </span>
      {trailing && <span className="ltr shrink-0 font-semibold tabular-nums text-brand-800">{trailing}</span>}
    </button>
  );
}
