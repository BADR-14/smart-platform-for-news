import { Elements, PaymentElement, useElements, useStripe } from '@stripe/react-stripe-js';
import { loadStripe } from '@stripe/stripe-js';
import type { Stripe } from '@stripe/stripe-js';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { CreditCard, Lock } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { toast } from 'sonner';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/FormField';
import { Modal } from '@/components/ui/Modal';
import { errorMessage } from '@/lib/api/errors';
import { formatMoney } from '@/lib/utils/format';
import { companyApi } from '../api';
import type { TopUpIntent } from '../api';
import { billingKeys } from '../keys';

const QUICK_AMOUNTS = [10, 25, 50, 100];
const MIN = 5;
const MAX = 10_000;

interface TopUpDialogProps {
  currency: string;
  onClose: () => void;
}

/**
 * شحن المحفظة على خطوتين:
 *  1) اختيار المبلغ ← الباك-إند ينشئ PaymentIntent ويرجع client_secret.
 *  2) إدخال بيانات البطاقة داخل نموذج Stripe (البيانات لا تمر بخوادمنا) ثم تأكيد الشحن.
 */
export function TopUpDialog({ currency, onClose }: TopUpDialogProps) {
  const [amount, setAmount] = useState('25');
  const [intent, setIntent] = useState<TopUpIntent | null>(null);
  const [stripePromise, setStripePromise] = useState<Promise<Stripe | null> | null>(null);

  const numericAmount = Number(amount);
  const validAmount = Number.isFinite(numericAmount) && numericAmount >= MIN && numericAmount <= MAX;

  const initiate = useMutation({
    mutationFn: () => companyApi.initiateTopUp(numericAmount),
    onSuccess: (created) => {
      setIntent(created);
      // loadStripe يُستدعى مرة واحدة لكل عملية دفع، لا مع كل إعادة رسم
      setStripePromise(created.publishable_key ? loadStripe(created.publishable_key) : null);
    },
  });

  return (
    <Modal open onClose={onClose} size="md" title="شحن المحفظة" description="الدفع آمن عبر Stripe.">
      {!intent ? (
        <form
          className="flex flex-col gap-4"
          onSubmit={(event) => {
            event.preventDefault();
            if (validAmount) initiate.mutate();
          }}
        >
          {initiate.error && <Alert>{errorMessage(initiate.error)}</Alert>}

          <div className="grid grid-cols-4 gap-2">
            {QUICK_AMOUNTS.map((value) => (
              <button
                key={value}
                type="button"
                onClick={() => setAmount(String(value))}
                aria-pressed={numericAmount === value}
                className={
                  numericAmount === value
                    ? 'ltr rounded-xl border border-accent-500 bg-accent-50 py-2.5 text-sm font-semibold text-accent-700 ring-1 ring-accent-500'
                    : 'ltr rounded-xl border border-line-strong py-2.5 text-sm font-semibold text-ink-soft hover:border-brand-300'
                }
              >
                {formatMoney(value, currency)}
              </button>
            ))}
          </div>

          <Input
            label={`المبلغ (${currency})`}
            type="number"
            min={MIN}
            max={MAX}
            step="0.01"
            dir="ltr"
            required
            value={amount}
            onChange={(event) => setAmount(event.target.value)}
            error={amount !== '' && !validAmount ? `المبلغ يجب أن يكون بين ${MIN} و ${MAX.toLocaleString('en-US')}` : undefined}
          />

          <Button type="submit" size="lg" icon={<CreditCard className="size-5" />} disabled={!validAmount} loading={initiate.isPending}>
            متابعة للدفع
          </Button>
        </form>
      ) : stripePromise ? (
        <Elements stripe={stripePromise} options={{ clientSecret: intent.client_secret, locale: 'ar', appearance: stripeAppearance }}>
          <PaymentForm intent={intent} onBack={() => setIntent(null)} onDone={onClose} />
        </Elements>
      ) : (
        <Alert title="بوابة الدفع غير مهيّأة">مفتاح Stripe العام غير مضبوط في إعدادات الخادم (STRIPE_KEY).</Alert>
      )}
    </Modal>
  );
}

const stripeAppearance = {
  theme: 'stripe' as const,
  variables: {
    colorPrimary: '#1a344b',
    colorDanger: '#d14343',
    borderRadius: '12px',
    fontFamily: 'IBM Plex Sans Arabic, Tahoma, sans-serif',
  },
};

interface PaymentFormProps {
  intent: TopUpIntent;
  onBack: () => void;
  onDone: () => void;
}

function PaymentForm({ intent, onBack, onDone }: PaymentFormProps) {
  const stripe = useStripe();
  const elements = useElements();
  const queryClient = useQueryClient();
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault();
    if (!stripe || !elements) return;

    setSubmitting(true);
    setError(null);

    try {
      // 1) Stripe يؤكد الدفع مباشرة من المتصفح
      const result = await stripe.confirmPayment({ elements, redirect: 'if_required' });

      if (result.error) {
        setError(result.error.message ?? 'تعذّر إتمام الدفع.');
        return;
      }

      // 2) الباك-إند يتحقق من Stripe ثم يضيف الرصيد (لا نثق بنتيجة المتصفح)
      const message = await companyApi.confirmTopUp(intent.payment_intent_id);

      toast.success(message);
      await Promise.all(billingKeys.map((queryKey) => queryClient.invalidateQueries({ queryKey })));
      onDone();
    } catch (caught) {
      setError(errorMessage(caught));
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="flex flex-col gap-4">
      <div className="flex items-center justify-between rounded-xl bg-brand-50 px-4 py-3">
        <span className="text-sm text-ink-soft">المبلغ</span>
        <span className="ltr text-lg font-bold tabular-nums text-brand-800">{formatMoney(intent.amount, intent.currency)}</span>
      </div>

      {error && <Alert>{error}</Alert>}

      <PaymentElement />

      <p className="flex items-center gap-1.5 text-xs text-muted">
        <Lock className="size-3.5" />
        بيانات البطاقة تُرسل مباشرة إلى Stripe ولا تُخزَّن لدينا.
      </p>

      <div className="flex justify-end gap-2">
        <Button variant="ghost" onClick={onBack} disabled={submitting}>
          رجوع
        </Button>
        <Button type="submit" variant="accent" loading={submitting} disabled={!stripe || !elements}>
          ادفع {formatMoney(intent.amount, intent.currency)}
        </Button>
      </div>
    </form>
  );
}
