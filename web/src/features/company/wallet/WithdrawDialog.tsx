import { useState } from 'react';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/FormField';
import { Modal } from '@/components/ui/Modal';
import { useActionMutation } from '@/lib/hooks/useActionMutation';
import { formatMoney } from '@/lib/utils/format';
import { companyApi } from '../api';
import { billingKeys } from '../keys';

interface WithdrawDialogProps {
  balance: number;
  currency: string;
  onClose: () => void;
}

export function WithdrawDialog({ balance, currency, onClose }: WithdrawDialogProps) {
  const [amount, setAmount] = useState('');
  const [description, setDescription] = useState('');

  const numericAmount = Number(amount);
  const valid = Number.isFinite(numericAmount) && numericAmount >= 1 && numericAmount <= balance;

  const withdraw = useActionMutation({
    mutationFn: () => companyApi.withdraw(numericAmount, description.trim() || undefined),
    invalidate: billingKeys,
    onSuccess: onClose,
  });

  return (
    <Modal
      open
      onClose={onClose}
      size="sm"
      title="سحب من المحفظة"
      description={`الرصيد المتاح: ${formatMoney(balance, currency)}`}
      footer={
        <>
          <Button variant="ghost" onClick={onClose} disabled={withdraw.isPending}>
            إلغاء
          </Button>
          <Button type="submit" form="withdraw-form" disabled={!valid} loading={withdraw.isPending}>
            تأكيد السحب
          </Button>
        </>
      }
    >
      <form
        id="withdraw-form"
        className="flex flex-col gap-4"
        onSubmit={(event) => {
          event.preventDefault();
          if (valid) withdraw.mutate();
        }}
      >
        <Input
          label={`المبلغ (${currency})`}
          type="number"
          min={1}
          max={balance}
          step="0.01"
          dir="ltr"
          required
          autoFocus
          value={amount}
          onChange={(event) => setAmount(event.target.value)}
          error={amount !== '' && !valid ? 'المبلغ يجب أن يكون 1 على الأقل ولا يتجاوز الرصيد المتاح' : undefined}
        />
        <Input label="ملاحظة (اختياري)" maxLength={255} value={description} onChange={(event) => setDescription(event.target.value)} />
      </form>
    </Modal>
  );
}
