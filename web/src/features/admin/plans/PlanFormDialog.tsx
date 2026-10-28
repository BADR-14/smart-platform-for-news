import { zodResolver } from '@hookform/resolvers/zod';
import { useState } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { z } from 'zod';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/FormField';
import { Modal } from '@/components/ui/Modal';
import { useActionMutation } from '@/lib/hooks/useActionMutation';
import { applyServerErrors } from '@/lib/utils/form';
import { formatMoney } from '@/lib/utils/format';
import { adminApi } from '../api';
import type { Plan } from '../api';
import { adminKeys } from '../keys';

const schema = z.object({
  name: z.string().trim().min(1, 'اسم الباقة مطلوب').max(255),
  posts_count: z.number({ error: 'أدخل عدداً صحيحاً' }).int('أدخل عدداً صحيحاً').min(1, 'منشور واحد على الأقل').max(1000),
  price: z.number({ error: 'أدخل سعراً صالحاً' }).min(0, 'السعر لا يكون سالباً').max(1_000_000),
  is_active: z.boolean(),
});

type FormValues = z.infer<typeof schema>;

interface PlanFormDialogProps {
  /** null = إنشاء باقة جديدة. */
  plan: Plan | null;
  onClose: () => void;
}

export function PlanFormDialog({ plan, onClose }: PlanFormDialogProps) {
  const [formError, setFormError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    control,
    setError,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      name: plan?.name ?? '',
      posts_count: plan?.posts_count ?? 5,
      price: plan?.price ?? 0,
      is_active: plan?.is_active ?? true,
    },
  });

  const save = useActionMutation({
    mutationFn: (values: FormValues) => (plan ? adminApi.updatePlan(plan.id, values) : adminApi.createPlan(values)),
    invalidate: [adminKeys.plans],
    errorToast: false,
    onSuccess: onClose,
  });

  const [postsCount, price] = useWatch({ control, name: ['posts_count', 'price'] });
  const perPost = postsCount > 0 && price >= 0 ? price / postsCount : null;

  const onSubmit = handleSubmit(async (values) => {
    setFormError(null);

    try {
      await save.mutateAsync(values);
    } catch (error) {
      setFormError(applyServerErrors(error, setError, ['name', 'posts_count', 'price']));
    }
  });

  return (
    <Modal
      open
      onClose={onClose}
      title={plan ? 'تعديل الباقة' : 'باقة جديدة'}
      size="sm"
      footer={
        <>
          <Button variant="ghost" onClick={onClose} disabled={save.isPending}>
            إلغاء
          </Button>
          <Button type="submit" form="plan-form" loading={save.isPending}>
            حفظ
          </Button>
        </>
      }
    >
      <form id="plan-form" onSubmit={onSubmit} noValidate className="flex flex-col gap-4">
        {formError && <Alert>{formError}</Alert>}

        <Input label="اسم الباقة" required placeholder="مثال: الباقة الأساسية" error={errors.name?.message} {...register('name')} />

        <div className="grid grid-cols-2 gap-3">
          <Input
            label="عدد المنشورات المميزة"
            type="number"
            min={1}
            required
            dir="ltr"
            error={errors.posts_count?.message}
            {...register('posts_count', { valueAsNumber: true })}
          />
          <Input
            label="سعر الباقة (USD)"
            type="number"
            min={0}
            step="0.01"
            required
            dir="ltr"
            error={errors.price?.message}
            {...register('price', { valueAsNumber: true })}
          />
        </div>

        {perPost !== null && Number.isFinite(perPost) && (
          <p className="rounded-xl bg-brand-50 px-4 py-2.5 text-sm text-brand-800">
            تكلفة المنشور المميز ضمن هذه الباقة: <strong className="ltr tabular-nums">{formatMoney(perPost)}</strong>
          </p>
        )}

        <label className="flex cursor-pointer items-center gap-2 text-sm text-ink-soft">
          <input type="checkbox" className="size-4 accent-accent-500" {...register('is_active')} />
          الباقة متاحة للشراء
        </label>
      </form>
    </Modal>
  );
}
