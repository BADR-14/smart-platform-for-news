import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { toast } from 'sonner';
import { z } from 'zod';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { Input } from '@/components/ui/FormField';
import { isAdminSite } from '@/config/env';
import { DetailRow, Switch } from '@/components/ui/Misc';
import { PageHeader } from '@/components/ui/PageHeader';
import { LoadingState } from '@/components/ui/States';
import { authApi } from '@/features/auth/api';
import { passwordSchema } from '@/features/auth/schemas';
import { useAuth } from '@/features/auth/useAuth';
import { notificationsApi } from '@/features/notifications/api';
import type { NotificationPreferences } from '@/features/notifications/api';
import { notificationKeys } from '@/features/notifications/hooks';
import { errorMessage } from '@/lib/api/errors';
import { applyServerErrors } from '@/lib/utils/form';

/** إعدادات الحساب — مشتركة بين لوحة الأدمن ولوحة الشركة. */
export function SettingsPage() {
  const { account } = useAuth();

  return (
    <>
      <PageHeader
        title="الإعدادات"
        description={isAdminSite ? 'بيانات الحساب وتفضيلات الإشعارات.' : 'بيانات الحساب، كلمة المرور، وتفضيلات الإشعارات.'}
      />

      <div className="grid gap-6 lg:grid-cols-2">
        <div className="space-y-6">
          <Card>
            <CardHeader title="بيانات الحساب" />
            <CardBody>
              <dl className="divide-y divide-line">
                <DetailRow label="الاسم">{account?.name}</DetailRow>
                <DetailRow label="البريد الإلكتروني">
                  <span className="ltr">{account?.email}</span>
                </DetailRow>
                <DetailRow label="نوع الحساب">{account?.type === 'admin' ? 'مدير النظام' : 'شركة'}</DetailRow>
              </dl>
            </CardBody>
          </Card>

          <NotificationPreferencesCard />
        </div>

        {/* الإدارة تدخل بالرابط السحري ولا كلمة مرور لها */}
        {!isAdminSite && <ChangePasswordCard />}
      </div>
    </>
  );
}

// ==================== كلمة المرور ====================

const passwordFormSchema = z
  .object({
    current_password: z.string().min(1, 'أدخل كلمة المرور الحالية'),
    new_password: passwordSchema,
    new_password_confirmation: z.string().min(1, 'أعد كتابة كلمة المرور الجديدة'),
  })
  .refine((values) => values.new_password === values.new_password_confirmation, {
    path: ['new_password_confirmation'],
    message: 'كلمتا المرور غير متطابقتين',
  })
  .refine((values) => values.new_password !== values.current_password, {
    path: ['new_password'],
    message: 'كلمة المرور الجديدة يجب أن تختلف عن الحالية',
  });

type PasswordFormValues = z.infer<typeof passwordFormSchema>;

function ChangePasswordCard() {
  const [formError, setFormError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<PasswordFormValues>({
    resolver: zodResolver(passwordFormSchema),
    defaultValues: { current_password: '', new_password: '', new_password_confirmation: '' },
  });

  const onSubmit = handleSubmit(async (values) => {
    setFormError(null);

    try {
      toast.success(await authApi.changePassword(values));
      reset();
    } catch (error) {
      setFormError(applyServerErrors(error, setError, ['current_password', 'new_password']));
    }
  });

  return (
    <Card className="h-fit">
      <CardHeader title="تغيير كلمة المرور" description="بعد التغيير تُنهى جلساتك على الأجهزة الأخرى." />
      <CardBody>
        <form onSubmit={onSubmit} noValidate className="flex flex-col gap-4">
          {formError && <Alert>{formError}</Alert>}

          <Input
            label="كلمة المرور الحالية"
            type="password"
            autoComplete="current-password"
            dir="ltr"
            error={errors.current_password?.message}
            {...register('current_password')}
          />
          <Input
            label="كلمة المرور الجديدة"
            type="password"
            autoComplete="new-password"
            dir="ltr"
            hint="8 محارف على الأقل، وتحتوي على حرف ورقم."
            error={errors.new_password?.message}
            {...register('new_password')}
          />
          <Input
            label="تأكيد كلمة المرور الجديدة"
            type="password"
            autoComplete="new-password"
            dir="ltr"
            error={errors.new_password_confirmation?.message}
            {...register('new_password_confirmation')}
          />

          <div className="flex justify-end">
            <Button type="submit" loading={isSubmitting}>
              حفظ كلمة المرور
            </Button>
          </div>
        </form>
      </CardBody>
    </Card>
  );
}

// ==================== الإشعارات ====================

function NotificationPreferencesCard() {
  const queryClient = useQueryClient();

  const query = useQuery({ queryKey: notificationKeys.preferences, queryFn: notificationsApi.getPreferences });

  const mutation = useMutation({
    mutationFn: notificationsApi.updatePreferences,
    // تحديث فوري للواجهة، والتراجع إذا فشل الحفظ
    onMutate: async (next: NotificationPreferences) => {
      await queryClient.cancelQueries({ queryKey: notificationKeys.preferences });
      const previous = queryClient.getQueryData<NotificationPreferences>(notificationKeys.preferences);
      queryClient.setQueryData(notificationKeys.preferences, next);
      return { previous };
    },
    onError: (error, _next, context) => {
      queryClient.setQueryData(notificationKeys.preferences, context?.previous);
      toast.error(errorMessage(error));
    },
    onSuccess: () => toast.success('تم حفظ تفضيلات الإشعارات'),
  });

  const preferences = query.data;

  return (
    <Card>
      <CardHeader title="الإشعارات" />
      <CardBody className="space-y-5">
        {!preferences ? (
          <LoadingState className="py-6" />
        ) : (
          <>
            <Switch
              label="إشعارات البريد الإلكتروني"
              description="استلام نسخة من الإشعارات المهمة على بريدك."
              checked={preferences.email_notifications}
              disabled={mutation.isPending}
              onChange={(checked) => mutation.mutate({ ...preferences, email_notifications: checked })}
            />
            <Switch
              label="إشعارات تسجيل الدخول"
              description="إشعار عند كل تسجيل دخول جديد إلى حسابك."
              checked={preferences.login_notifications}
              disabled={mutation.isPending}
              onChange={(checked) => mutation.mutate({ ...preferences, login_notifications: checked })}
            />
          </>
        )}
      </CardBody>
    </Card>
  );
}
