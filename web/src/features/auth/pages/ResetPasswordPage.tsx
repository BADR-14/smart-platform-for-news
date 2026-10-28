import { zodResolver } from '@hookform/resolvers/zod';
import { Lock } from 'lucide-react';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { Link, useSearchParams } from 'react-router-dom';
import { z } from 'zod';
import { AuthLayout } from '@/components/layout/AuthLayout';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/FormField';
import { applyServerErrors } from '@/lib/utils/form';
import { authApi } from '../api';
import { passwordSchema } from '../schemas';

const schema = z
  .object({
    password: passwordSchema,
    password_confirmation: z.string().min(1, 'أعد كتابة كلمة المرور'),
  })
  .refine((values) => values.password === values.password_confirmation, {
    path: ['password_confirmation'],
    message: 'كلمتا المرور غير متطابقتين',
  });

type FormValues = z.infer<typeof schema>;

/** الصفحة التي يفتحها رابط البريد: /reset-password?token=...&email=... */
export function ResetPasswordPage() {
  const [params] = useSearchParams();
  const token = params.get('token');
  const email = params.get('email');

  const [doneMessage, setDoneMessage] = useState<string | null>(null);
  const [formError, setFormError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { password: '', password_confirmation: '' },
  });

  if (!token || !email) {
    return (
      <AuthLayout title="رابط غير صالح">
        <Alert>
          هذا الرابط ناقص أو غير صالح.{' '}
          <Link to="/forgot-password" className="font-semibold underline">
            اطلب رابطاً جديداً
          </Link>
        </Alert>
      </AuthLayout>
    );
  }

  const onSubmit = handleSubmit(async (values) => {
    setFormError(null);

    try {
      setDoneMessage(await authApi.resetPassword({ email, token, ...values }));
    } catch (error) {
      setFormError(applyServerErrors(error, setError, ['password']));
    }
  });

  return (
    <AuthLayout title="كلمة مرور جديدة" description={`للحساب: ${email}`}>
      {doneMessage ? (
        <div className="flex flex-col gap-4">
          <Alert tone="success">{doneMessage}</Alert>
          <Link to="/login">
            <Button size="lg" className="w-full">
              تسجيل الدخول
            </Button>
          </Link>
        </div>
      ) : (
        <form onSubmit={onSubmit} noValidate className="flex flex-col gap-4">
          {formError && <Alert>{formError}</Alert>}

          <Input
            label="كلمة المرور الجديدة"
            type="password"
            autoComplete="new-password"
            dir="ltr"
            hint="8 محارف على الأقل، وتحتوي على حرف ورقم."
            leading={<Lock className="size-4" />}
            error={errors.password?.message}
            {...register('password')}
          />

          <Input
            label="تأكيد كلمة المرور"
            type="password"
            autoComplete="new-password"
            dir="ltr"
            leading={<Lock className="size-4" />}
            error={errors.password_confirmation?.message}
            {...register('password_confirmation')}
          />

          <Button type="submit" size="lg" loading={isSubmitting}>
            حفظ كلمة المرور
          </Button>
        </form>
      )}
    </AuthLayout>
  );
}
