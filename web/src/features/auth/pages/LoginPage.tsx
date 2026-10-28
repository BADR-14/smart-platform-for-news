import { zodResolver } from '@hookform/resolvers/zod';
import { Lock, LogIn, Mail } from 'lucide-react';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { z } from 'zod';
import { AuthLayout } from '@/components/layout/AuthLayout';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/FormField';
import { toApiError } from '@/lib/api/errors';
import { homePathFor, useAuth } from '../useAuth';

const schema = z.object({
  email: z.email('أدخل بريداً إلكترونياً صالحاً'),
  password: z.string().min(1, 'كلمة المرور مطلوبة'),
  remember: z.boolean(),
});

type FormValues = z.infer<typeof schema>;

/** دخول موقع الشركات ببريد وكلمة مرور. */
export function LoginPage() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [formError, setFormError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { email: '', password: '', remember: true },
  });

  const onSubmit = handleSubmit(async ({ remember, ...credentials }) => {
    setFormError(null);

    try {
      const account = await login(credentials, remember);
      const from = (location.state as { from?: string } | null)?.from;
      const home = homePathFor(account);

      // نعود للصفحة المطلوبة فقط إذا كانت ضمن لوحة هذا الحساب
      navigate(from?.startsWith(home) ? from : home, { replace: true });
    } catch (error) {
      const apiError = toApiError(error);

      // حساب لم يؤكد بريده بعد: نكمل معه من خطوة رمز التحقق
      if (apiError.code === 'email_unverified') {
        navigate(`/register?email=${encodeURIComponent(credentials.email)}`, { replace: true });
        return;
      }

      setFormError(apiError.message);
    }
  });

  return (
    <AuthLayout
      title="دخول الشركات"
      description="ادخل إلى لوحة شركتك لإدارة منشوراتك ومحفظتك وباقاتك."
      footer={
        <p>
          ليس لشركتك حساب بعد؟{' '}
          <Link to="/register" className="font-semibold text-accent-700 hover:underline">
            سجّل شركتك الآن
          </Link>
        </p>
      }
    >
      <form onSubmit={onSubmit} noValidate className="flex flex-col gap-4">
        {formError && <Alert>{formError}</Alert>}

        <Input
          label="البريد الإلكتروني"
          type="email"
          autoComplete="email"
          placeholder="name@company.com"
          dir="ltr"
          leading={<Mail className="size-4" />}
          error={errors.email?.message}
          {...register('email')}
        />

        <Input
          label="كلمة المرور"
          type="password"
          autoComplete="current-password"
          dir="ltr"
          leading={<Lock className="size-4" />}
          error={errors.password?.message}
          {...register('password')}
        />

        <div className="flex items-center justify-between text-sm">
          <label className="flex cursor-pointer items-center gap-2 text-ink-soft">
            <input type="checkbox" className="size-4 accent-accent-500" {...register('remember')} />
            تذكّرني
          </label>
          <Link to="/forgot-password" className="font-medium text-accent-700 hover:underline">
            نسيت كلمة المرور؟
          </Link>
        </div>

        <Button type="submit" size="lg" loading={isSubmitting} icon={<LogIn className="size-5" />}>
          دخول
        </Button>
      </form>
    </AuthLayout>
  );
}
