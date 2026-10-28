import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import { z } from 'zod';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { Input } from '@/components/ui/FormField';
import { useActionMutation } from '@/lib/hooks/useActionMutation';
import { adminApi } from '../api';
import type { PlatformSettings } from '../api';
import { adminKeys } from '../keys';

const schema = z.object({
  featured_post_price: z.number({ error: 'أدخل سعراً صالحاً' }).min(0, 'السعر لا يكون سالباً').max(100_000),
  featured_post_duration_days: z.number({ error: 'أدخل عدد أيام صالحاً' }).int().min(1, 'يوم واحد على الأقل').max(365),
});

/** سعر ومدة المنشور المميز — يحددهما الأدمن ويُطبّقان على كل تمييز جديد. */
export function FeaturedSettingsCard({ settings }: { settings: PlatformSettings }) {
  const {
    register,
    handleSubmit,
    formState: { errors, isDirty },
    reset,
  } = useForm<PlatformSettings>({ resolver: zodResolver(schema), defaultValues: settings });

  const save = useActionMutation({
    mutationFn: adminApi.updateSettings,
    invalidate: [adminKeys.plans],
  });

  return (
    <Card className="h-fit">
      <CardHeader title="إعدادات المنشور المميز" description="للدفع من رصيد المحفظة مباشرة بدون باقة." />
      <CardBody>
        <form
          noValidate
          className="flex flex-col gap-4"
          onSubmit={handleSubmit((values) => save.mutate(values, { onSuccess: () => reset(values) }))}
        >
          <Input
            label="سعر المنشور المميز الواحد (USD)"
            type="number"
            min={0}
            step="0.01"
            dir="ltr"
            error={errors.featured_post_price?.message}
            {...register('featured_post_price', { valueAsNumber: true })}
          />
          <Input
            label="مدة التمييز (بالأيام)"
            type="number"
            min={1}
            dir="ltr"
            hint="المدة التي يبقى فيها المنشور أعلى الصفحة الرئيسية."
            error={errors.featured_post_duration_days?.message}
            {...register('featured_post_duration_days', { valueAsNumber: true })}
          />
          <Button type="submit" loading={save.isPending} disabled={!isDirty}>
            حفظ الإعدادات
          </Button>
        </form>
      </CardBody>
    </Card>
  );
}
